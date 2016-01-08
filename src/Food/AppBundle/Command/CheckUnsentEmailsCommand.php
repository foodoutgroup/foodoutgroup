<?php
namespace Food\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckUnsentEmailsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('email:check:unsent')
            ->setDescription('Check for unsent email messages')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailService = $this->getContainer()->get('food.mail');

        $critical = false;
        $from = new \DateTime("-1 hours");
        $to = new \DateTime("-30 munute");
        try {
            $unsentEmails = $mailService->getUnsentEmailsForRange($from, $to);
            $this->getContainer()->get('doctrine')->getConnection()->close();
            $messagesCount = count($unsentEmails);

            if ($messagesCount > 0) {
                $text = sprintf(
                    '<error>ERROR: %d unsent email messages! Check Cron if it is working</error>',
                    $messagesCount
                );
                $critical = true;
            } else {
                $text = '<info>OK: all emails are sent. What a relief</info>';
            }
        } catch (\Exception $e) {
            $text = '<error>Error in unsent emails check: '.$e->getMessage().'</error>';
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