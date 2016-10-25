<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommand extends ContainerAwareCommand
{
    private $timeStart;
    private $maxChecks = 2;

    protected function configure()
    {
        $this->timeStart = microtime(true);

        $this
            ->setName('sms:send')
            ->setDescription('Send messages')
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

        $messagingService = $this->getContainer()->get('food.messages');
        $logger = $this->getContainer()->get('logger');
        $messagingProviders = $this->getContainer()->getParameter('sms.available_providers');
        $mainProvider = $this->getContainer()->getParameter('sms.main_provider');
//
        if (empty($mainProvider)) {
            $errMessage = 'No messaging providers configured. Please check Your configuration!';
            $output->writeln('<error>'.$errMessage.'</error>');

            throw new \Exception($errMessage);
        }

        $provider = $this->getContainer()->get($mainProvider);

        if ($input->getOption('debug')) {
            $provider->setDebugEnabled(true);
        }
        $messagingService->setMessagingProvider($provider);

        try {
            for ($timesChecked = 1; $timesChecked <= $this->getMaxChecks(); $timesChecked++) {
                $unsentMessages = $messagingService->getUnsentMessages();
                $unsentMessagesCount = count($unsentMessages);
                $output->writeln(sprintf('<info>Running %d of %d check itterarion.</info>', $timesChecked, $this->getMaxChecks()));
                $output->writeln(sprintf('<info>%d unsent messages found. Starting to send them now!</info>', $unsentMessagesCount));

                if (!empty($unsentMessages) && $unsentMessagesCount > 0) {
                    foreach($unsentMessages as $message) {
                        $providerChanged = false;
                        // If we have tried 3 trimes, maby lets change provider as it takes too long now
                        if ($message->getTimesSent() >= 2) {
                            if ($message->getSmsc() == 'InfoBip') {
                                // Pasitikrinam as Silverstreet isvis ijungtas
                                if (in_array('food.silverstreet', $messagingProviders)) {
                                    $altProvider = $this->getContainer()->get('food.silverstreet');
                                } else {
                                    $altProvider = $this->getContainer()->get('food.infobip');
                                }
                            } else {
                                $altProvider = $this->getContainer()->get('food.infobip');
                            }

                            $messagingService->setMessagingProvider($altProvider);

                            $providerChanged = true;
                        }

                        $output->writeln(sprintf('<info>Sending message id: %d</info>', $message->getId()));
                        $messagingService->sendMessage($message);
                        $messagingService->saveMessage($message);
                        $count++;

                        // Lets get back to main provider
                        if ($providerChanged) {
                            $messagingService->setMessagingProvider($provider);
                        }

                        // Jei uztrukom ilgiau nei 80s - nustojam sukt checkus, nes greit pasileis naujas instance
                        if ((microtime(true) - $this->timeStart) > 80) {
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
            $output->writeln(sprintf('<info>%d messages sent in %0.2f seconds</info>', $count, $timeSpent));
            // Log performance data
            $logger->alert(sprintf(
                '[Performance] SMS sent cron send %d messages in %0.2f seconds',
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
