<?php
namespace Food\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\UserBundle\Entity\UserAddress;

class MailerSubscribersUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mailer:subscribers:update')
            ->setDescription('Update subscribers list for mailer.lt')
            ->setHelp('You must specify a list ID --list_id option. Ex.: 2023873 (more at: foodout.mailer.lt/groups/)')
            ->addOption('list_id', null, InputOption::VALUE_REQUIRED, 'Specify a list ID, for example: --list_id 2023873')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $list_id = $input->getOption('list_id');
        if (!$list_id) {
            throw new \InvalidArgumentException('You must specify a --list_id. Example: --list_id 2023873');
        }
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();

            // 1. pirkusius userius
            $users_by_orders = $this->getUsersByOrders();
            $this->importUsersToServer('Orders', $users_by_orders, $list_id, $output);

            // 2. tuos, kurie uzsisako naujienlaiskio prenumerata
            $users_by_subscribe = $em->getRepository('FoodAppBundle:Subscribers')->findAll();
            $this->importUsersToServer('Subscribers', $users_by_subscribe, $list_id, $output);

            // 3. Tuos, kurie dalyvauja zaidimuose ir krenta i marketing user admin'e
            $users_by_marketing = $em->getRepository('FoodAppBundle:MarketingUser')->findAll();
            $this->importUsersToServer('Marketing', $users_by_marketing, $list_id, $output);
        } catch (\Exception $e) {
            $output->writeln('[!] Error when updating mailer subscribers');
            $output->writeln('[!] Error: '.$e->getMessage());
            $output->writeln('[!] Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }

    protected function getServices()
    {
        $container = $this->getContainer();
        $services = new \StdClass();
        $services->em = $container->get('doctrine.orm.entity_manager');
        // critical in batch sql operations! disabling will prevent memory leaks.
        $services->em->getConnection()->getConfiguration()->setSQLLogger(null);
        return $services;
    }

    protected function getUsersByOrders()
    {
        $qb = $this->getServices()->em->createQueryBuilder();
        $result = $qb->select('distinct u')
            ->from('FoodOrderBundle:Order', 'o')
            ->innerJoin('FoodUserBundle:User', 'u', 'WITH', 'o.user = u.id')
            ->where("o.order_status = 'completed'")
            ->andWhere('u.enabled = 1')
            ->andWhere('u.expired = 0')
            ->andWhere('u.locked = 0')
            ->andWhere('u.fully_registered = 1')
            ->getQuery()
            ->getResult();
        return $result;
    }

    protected function importUsersToServer($type, $users, $list_id, $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $mailer = $this->getContainer()->get('food.mailer');
        $output->write(sprintf("[*] Found %d " . $type . " users. Importing data to mailer.lt", count($users))."\n");
        $processedUsers = 0;
        $subscribers = array();

        if (empty($users)) {
            throw new \InvalidArgumentException('[!] Error: No ' . $type . ' users found in database.');
        }

        foreach ($users as $user) {
            switch ($type) {
                case 'Orders':
                    if (!$user->isSuperAdmin()) {
                        $city = null;
                        $address = $em->getRepository('FoodUserBundle:UserAddress')->findOneBy(array('user' => $user));
                        if ($address instanceof UserAddress && $address && $address->getId() != '') {
                            $city = $address->getCity();
                        }
                        $subscribers[] = array(
                            'email' => $user->getEmail(),
                            'name' => $user->getFirstname(),
                            'fields' => array(
                                array('name' => 'city', 'value' => $city)
                            )
                        );
                    }
                    break;
                case 'Subscribers':
                    $subscribers[] = array(
                        'email' => $user->getEmail(),
                    );
                    break;
                case 'Marketing':
                    $subscribers[] = array(
                        'email' => $user->getEmail(),
                        'name' => $user->getFirstname(),
                        'fields' => array(
                            array('name' => 'city', 'value' => $user->getCity())
                        )
                    );
                    break;
            }
            $processedUsers++;
        }

        if ($processedUsers > 0) {
            $em->flush();
        }

        /* Set it to '1', if you want to reactivate subscriber (default '0') */
        $subscribers_all = $mailer->setId($list_id)->addAll($subscribers, 0);
        if (!empty($subscribers_all['Results']) && count($subscribers_all['Results']) > 0) {
            $output->writeln('[+] ' . $type . ' Data imported successfully.');
            $output->writeln('[+] Response messages from mailer.lt server:');
            $output->writeln('----------------------------------------------------------------');
            foreach($subscribers_all['Results'] as $result) {
                $recipient = null;
                if (isset($result['email'])) {
                    $recipient = $result['email'];
                } else if (isset($result['emails'])) {
                    $recipient = $result['emails'];
                }
                $output->writeln('[-] User: ' . $recipient . ' - ' . $result['message']);
            }
        }
        $output->writeln('----------------------------------------------------------------');
        $output->writeln('[*] Total ' . $type . ' users processed: ' . $processedUsers);
        $output->writeln('[*] Total ' . $type . ' users imported: ' . ($processedUsers - count($subscribers_all['Results'])));
        $output->writeln('----------------------------------------------------------------');
    }
}
