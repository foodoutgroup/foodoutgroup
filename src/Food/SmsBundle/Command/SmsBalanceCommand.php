<?php
namespace Food\SmsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SmsBalanceCommand
 * @package Food\SmsBundle\Command
 */
class SmsBalanceCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sms:check:balance')
            ->setDescription('Check SMS prioviders balance')
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
        $messagingService = $this->getContainer()->get('food.messages');
        $messagingProviders = $this->getContainer()->getParameter('sms.available_providers');

        $balanceList =array();

        $warnLimit = $this->getContainer()->getParameter('sms.balance_limit_warn');
        $criticalLimit = $this->getContainer()->getParameter('sms.balance_limit_critical');
        $criticalCount = 0;
        $warnCount = 0;

        foreach($messagingProviders as $providerName) {
            $thisCritical = false;
            $thisWarning = false;
            $provider = $this->getContainer()->get($providerName);
            $messagingService->setMessagingProvider($provider);

            try {
                $balance = $messagingService->getAccountBalance();

                if ($balance < $criticalLimit) {
                    $criticalCount++;
                    $thisCritical = true;
                } else if ($balance < $warnLimit) {
                    $warnCount++;
                    $thisWarning = true;
                }

                $balanceList[$providerName] = array(
                    'name' => $provider->getProviderName(),
                    'balance' => $balance,
                    'error' => null,
                    'critical' => $thisCritical,
                    'warning' => $thisWarning,
                );
            } catch (\Exception $e) {
                $criticalCount++;

                $balanceList[$providerName] = array(
                    'name' => $provider->getProviderName(),
                    'balance' => '',
                    'error' => 'ERROR: '.$e->getMessage(),
                    'critical' => true,
                    'warning' => false,
                );
            }
        }

        // Output formating:
        if ($criticalCount > 0) {
            $mainMessage = sprintf('<error>Critical: %d of providers has criticaly low balance</error>', $criticalCount);
        } else if ($warnCount > 0) {
            $mainMessage = sprintf('<comment>Warning: %d of providers soon will be low on balance</comment>', $warnCount);
        } else {
            $mainMessage = '<info>OK: all providers have enough of money</info>';
        }
        $output->writeln($mainMessage);

        foreach ($balanceList as $providerBalance) {
            $level = 'info';
            if ($providerBalance['critical']) {
                $level = 'error';
            } else if ($providerBalance['warning']) {
                $level = 'comment';
            }

            $output->writeln(
                sprintf(
                    '<%1$s>%2$s - %3$s</%1$s>',
                    $level,
                    $providerBalance['name'],
                    (empty($providerBalance['error']) ? $providerBalance['balance'] : $providerBalance['error'])
                )
            );
        }

        if ($criticalCount > 0) {
            // TODO send das message, or we are doomed!
        }
    }
}