<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
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
//        $messagingProviders = $this->getContainer()->getParameter('sms.available_providers');
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
            $unsentMessages = $messagingService->getUnsentMessages();
            $unsentMessagesCount = count($unsentMessages);
            $output->writeln(sprintf('<info>%d unsent messages found. Starting to send them now!</info>', $unsentMessagesCount));

            if (!empty($unsentMessages) && $unsentMessagesCount > 0) {
                foreach($unsentMessages as $message) {
                    $output->writeln(sprintf('<info>Sending message id: %d</info>', $message->getId()));
                    $messagingService->sendMessage($message);
                    $messagingService->saveMessage($message);
                    $count++;
                }
            }

            $output->writeln(sprintf('<info>%d messages sent</info>', $count));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorry, lazy programmer left a bug :(</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        } catch (\Exception $e) {
            $output->writeln('<error>Mayday mayday, an error knocked the process down.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }
    }
}