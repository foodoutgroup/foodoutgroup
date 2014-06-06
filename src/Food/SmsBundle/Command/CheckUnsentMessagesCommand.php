<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUnsentMessagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sms:check:unsent')
            ->setDescription('Check')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messagingService = $this->getContainer()->get('food.messages');

        $from = new \DateTime("-1 hours");
        $to = new \DateTime("-5 minutes");
        try {
            $unsentMessages = $messagingService->getUnsentMessagesForRange($from, $to);
            $messagesCount = count($unsentMessages);

            if ($messagesCount > 0) {
                $text = sprintf(
                    '<error>ERROR: %d unsent messages!</error>',
                    $messagesCount
                );

                $this->soundTheAlarm($text);
            } else {
                $text = '<info>OK: all messages sent. Have a nice day</info>';
            }
        } catch (\Exception $e) {
            $text = 'Error in unsent messages check: '.$e->getMessage();
            $this->soundTheAlarm($text);

            throw $e;
        }

        $output->writeln($text);
    }

    protected function soundTheAlarm($text)
    {
        $text = str_replace(array('<error>', '</error>'), '', $text);
        $domain = $this->getContainer()->getParameter('domain');
        $adminEmails = $this->getContainer()->getParameter('admin.emails');
        $mailer = $this->getContainer()->get('mailer');

        $sendMonitoringMessages = $this->getContainer()->getParameter('admin.send_monitoring_message');
        $adminPhones = array();

        if (!empty($adminEmails)) {
            $message = \Swift_Message::newInstance()
                ->setSubject('Unsent messages monitoring error')
                ->setFrom('monitoring@'.$domain)
            ;

            foreach ($adminEmails as $email) {
                $message->addTo($email);
            }

            $message->setBody($text);
            $mailer->send($message);
        }

        if ($sendMonitoringMessages && !empty($adminPhones)) {
            $messagingService = $this->getContainer()->get('food.messages');
            // Rizikuojam siusdami per ji, nes jis stabiliausias, o luzis greiciausiai musu crono :(
            $provider = $this->getContainer()->get('food.infobip');
            $messagingService->setMessagingProvider($provider);

            $adminPhones = $this->getContainer()->getParameter('admin.phones');
            $sender = $this->getContainer()->getParameter('sms.sender');

            foreach ($adminPhones as $phone) {
                $textMessage = $messagingService->createMessage($sender, $phone, $text);
                $messagingService->sendMessage($textMessage);
                $messagingService->saveMessage($textMessage);
            }
        }
    }
}