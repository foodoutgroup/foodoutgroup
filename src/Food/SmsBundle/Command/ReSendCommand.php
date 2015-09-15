<?php
namespace Food\SmsBundle\Command;

use Food\SmsBundle\Entity\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReSendCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sms:resend')
            ->setDescription('Resend messages that was not delivered')
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
        $messagingProviders = $this->getContainer()->getParameter('sms.available_providers');

        if (count($messagingProviders) < 1) {
            $errMessage = 'No messaging providers configured. Please check Your configuration!';
            $output->writeln('<error>'.$errMessage.'</error>');

            throw new \Exception($errMessage);
        }

        // OMG kaip negrazu, bet cia laikinai, kol tik viena provideri turim
        try {
            $unsentMessages = $messagingService->getUndeliveredMessages();
            $unsentMessagesCount = count($unsentMessages);
            $output->writeln(sprintf('<info>%d stuck messages found. Starting to send them now!</info>', $unsentMessagesCount));

            if (!empty($unsentMessages) && $unsentMessagesCount > 0) {
                /**
                 * @var Message $message
                 */
                foreach($unsentMessages as $message) {
                    // TODO laikinas solutionas, po to sutvarkom
                    if ($message->getSmsc() == 'InfoBip') {
                        // Pasitikrinam as Silverstreet isvis ijungtas
                        if (in_array('food.silverstreet', $messagingProviders)) {
                            $provider = $this->getContainer()->get('food.silverstreet');
                        } else {
                            $provider = $this->getContainer()->get('food.infobip');
                        }
                    } else {
                        $provider = $this->getContainer()->get('food.infobip');
                    }

                    if ($input->getOption('debug')) {
                        $provider->setDebugEnabled(true);
                    }
                    $messagingService->setMessagingProvider($provider);

                    $output->writeln(
                        sprintf(
                            '<info>Resending message id: %d through provider: %s</info>',
                            $message->getId(),
                            $provider->getProviderName()
                        )
                    );
                    $messagingService->sendMessage($message);
                    $messagingService->saveMessage($message);
                    $count++;
                }
            }

            $output->writeln(sprintf('<info>%d messages sent</info>', $count));

            $this->getContainer()->get('doctrine')->getConnection()->close();
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