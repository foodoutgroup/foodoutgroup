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
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, debug information will be logged'
            )
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

                if ($input->getOption('debug')) {
                    foreach ($unsentEmails as $email) {
                        $output->writeln(
                            sprintf(
                                '<debug>Unsent message id: %d for order id: %d. Should be sent on: %s</debug>',
                                $email->getId(),
                                $email->getOrder()->getId(),
                                $email->getSendOnDate()->format("Y-m-d H:i:s")
                            )
                        );
                    }
                }
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