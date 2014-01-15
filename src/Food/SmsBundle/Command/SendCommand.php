<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        // TODO
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
        $messagingProviders = $this->getContainer()->getParameter('available_sms_providers');

        // TODO https://basecamp.com/2470154/projects/4420182-skanu-lt-gamyba/todos/72720237-antrinio
        if (count($messagingProviders) < 1) {
            $output->writeln('<error>No messaging providers configured. Please check Your configuration!</error>');
            return;
        }
        if (count($messagingProviders) > 1) {
            $output->writeln('<error>Sorry, at the moment we dont support more than one provider!</error>');
            $output->writeln('Available provider: '.var_export($messagingProviders, true));
            return;
        }

        // OMG kaip negrazu, bet cia laikinai, kol tik viena provideri turim
        if ($input->getOption('debug')) {
            $messagingProviders[0]->setDebugEnabled(true);
        }
        $messagingService->setMessagingProvider($messagingProviders[0]);

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
        } catch (Exception $e) {
            $output->writeln('<error>Mayday mayday, an error knocked the process down.</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>Sorry, lazy programmer left a bug :(</error>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
        }
    }
}