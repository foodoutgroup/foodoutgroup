<?php
namespace Food\UserBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Food\UserBundle\Entity\User;
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
            $mailer = $this->getContainer()->get('food.mailer');
            $users = $em->getRepository('FoodUserBundle:User')->findBy(array(
                'enabled' => true,
                'expired' => false,
                'locked' => false,
                'fully_registered' => 1,
            ));

            $output->write(sprintf("[*] Found %d users. Importing data to mailer.lt", count($users))."\n");
            $processedUsers = 0;
            $subscribers = array();

            if (empty($users)) {
                throw new \InvalidArgumentException('[!] Error: No users found in database.');
            }

            foreach ($users as $user) {
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
                    $processedUsers++;
                }
            }

            if ($processedUsers > 0) {
                $em->flush();
            }

            /* Set it to '1', if you want to reactivate subscriber (default '0') */
            $subscribers_all = $mailer->setId($list_id)->addAll($subscribers, 0);
            if (!empty($subscribers_all['Results']) && count($subscribers_all['Results']) > 0) {
                $output->writeln('[+] Data imported successfully.');
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
            $output->writeln('[*] Total users processed: ' . $processedUsers);
            $output->writeln('[*] Total users imported: ' . ($processedUsers - count($subscribers_all['Results'])));
        } catch (\Exception $e) {
            $output->writeln('[!] Error when updating mailer subscribers');
            $output->writeln('[!] Error: '.$e->getMessage());
            $output->writeln('[!] Trace: '."\n".$e->getTraceAsString());
            throw $e;
        }
    }
}
