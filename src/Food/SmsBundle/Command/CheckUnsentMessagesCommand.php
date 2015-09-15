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
            ->setDescription('Check for unsent messages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messagingService = $this->getContainer()->get('food.messages');

        $critical = false;
        $from = new \DateTime("-1 hours");
        $to = new \DateTime("-5 minutes");
        try {
            $unsentMessages = $messagingService->getUnsentMessagesForRange($from, $to);
            $this->getContainer()->get('doctrine')->getConnection()->close();
            $messagesCount = count($unsentMessages);

            if ($messagesCount > 0) {
                $text = sprintf(
                    '<error>ERROR: %d unsent messages!</error>',
                    $messagesCount
                );

            } else {
                $text = '<info>OK: all messages sent. Have a nice day</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>Error in unsent messages check: '.$e->getMessage().'</error>';
            $output->writeln($text);

            throw $e;
        }

        $output->writeln($text);

        if ($critical) {
            return 2;
        }

        return 0;
    }
}