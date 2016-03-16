<?php
namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailCommand extends ContainerAwareCommand
{
    private $timeStart;
    private $maxChecks = 5;

    protected function configure()
    {
        $this->timeStart = microtime(true);

        $this
            ->setName('email:send')
            ->setDescription('Send email messages')
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
        $count = 0;

        $mailService = $this->getContainer()->get('food.mail');
        $logger = $this->getContainer()->get('logger');
        $orderService = $this->getContainer()->get('food.order');

        try {
            for ($timesChecked = 1; $timesChecked <= $this->getMaxChecks(); $timesChecked++) {
                $unsentMails = $mailService->getEmailsToSend();
                $unsentMailsCount = count($unsentMails);
                $output->writeln(sprintf('<info>Running %d of %d check itterarion.</info>', $timesChecked, $this->getMaxChecks()));
                $output->writeln(sprintf('<info>%d unsent emails found. Starting to send them.</info>', $unsentMailsCount));

                if (!empty($unsentMails) && $unsentMailsCount > 0) {
                    foreach($unsentMails as $mail) {
                        try {
                            $output->writeln(sprintf(
                                '<info>Sending message id: %d of type: "%s" for order id: %d</info>',
                                $mail->getId(),
                                $mail->getType(),
                                $mail->getOrder()->getId()
                            ));

                            switch ($mail->getType()) {
                                case MailService::$typeCompleted:
                                    $orderService->setOrder($mail->getOrder());
                                    $orderService->sendCompletedMail();
                                    break;

                                case MailService::$typePartialyCompleted:
                                    $orderService->setOrder($mail->getOrder());
                                    $orderService->sendCompletedMail(true);
                                    break;

                                default:
                                    // do nothing - unknown type. Let support handle this
                                    $logger->error('Unknown email type found in mailing cron. Mail ID: ' . $mail->getId() . ' type: "' . $mail->getType() . '"');
                            }

                            $mailService->markEmailSent($mail);
                            $count++;
                        } catch (\Exception $e) {
                            $logger->error('Error while sending an email. Mail ID: ' . $mail->getId() . ' type: "' . $mail->getType() . '". Error: '.$e->getMessage());
                            $mailService->markAsError($mail, $e->getMessage());
                        }

                        // Jei uztrukom ilgiau nei 220s - nustojam sukt checkus, nes greit pasileis naujas instance
                        if ((microtime(true) - $this->timeStart) >= 230) {
                            break(2);
                        }
                    }
                }

                // Pailsim, jei tai ne paskutine iteracija
                if ($timesChecked != $this->getMaxChecks()) {
                    $output->writeln('<info>Sleeping for 10 seconds... zZzZzzZZzz...</info>');
                    sleep(10);
                }
            }

            $timeSpent = microtime(true) - $this->timeStart;
            $output->writeln(sprintf('<info>%d emails sent in %0.2f seconds</info>', $count, $timeSpent));
            // Log performance data
            $logger->alert(sprintf(
                '[Performance] Email sending cron sent %d emails in %0.2f seconds',
                $count,
                $timeSpent
            ));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorry, lazy programmer left a bug :(</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        } catch (\Exception $e) {
            $output->writeln('<error>Mayday mayday, an error knocked the process down.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
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
    public function setMaxChecks($checks=1)
    {
        $this->maxChecks = $checks;
    }
}