<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUndeliveredMessagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sms:check:undelivered')
            ->setDescription('Check for undelivered messages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $messagingService = $this->getContainer()->get('food.messages');

        $critical = false;
        $from = new \DateTime("-1 hours");
        $to = new \DateTime("-15 minutes");
        try {
            $unsentMessages = $messagingService->getUndeliveredMessagesForRange($from, $to);
            $this->getContainer()->get('doctrine')->getConnection()->close();
            $messagesCount = count($unsentMessages);

            if ($messagesCount > 7) {
                $critical = true;
                $text = sprintf(
                    '<error>ERROR: %d undelivered messages!</error>',
                    $messagesCount
                );
            } else {
                $text = '<info>OK: all messages delivered to client</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>Error in undelivered messages check: '.$e->getMessage().'</error>';
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