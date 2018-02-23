<?php

namespace Food\PushBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPushNotificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('push:send')
            ->setDescription('Send Push messages');


    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $pushService = $this->getContainer()->get('food.push');
        $pushes = $em->getRepository('FoodPushBundle:Push')->findBy(['sent' => 0]);


        try {
            if ($pushes) {
                foreach ($pushes as $push) {
                    if (!$push->getError()) {
                        $response = $pushService->sendPush($push);
                        $return = json_decode($response);

                        $check = isset($return->errors);

                        $push->setSubmittedAt(new \DateTime("now"));
                        if ($check) {
                            $push->setSent(false);
                            $push->setError(serialize($return->errors));
                        } else {
                            $push->setSent(true);
                            $push->setError(null);
                        }
                        $em->persist($push);
                        $em->flush();
                    }
                }
            }
            echo 'Jobs done. I am returning to town';
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

    /**
     * @return int
     */
    public function getMaxChecks()
    {
        return $this->maxChecks;
    }

    /**
     * @param int $checks
     */
    public function setMaxChecks($checks = 1)
    {
        $this->maxChecks = $checks;
    }
}