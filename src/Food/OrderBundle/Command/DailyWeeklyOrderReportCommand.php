<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DailyWeeklyOrderReportCommand extends ContainerAwareCommand
{
    const COMMAND = 'order:report:send';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const FORCE_EMAIL = 'force-email';
    const NOT_DRY_RUN = 'not-dry-run';

    protected function configure()
    {
        $this
            ->setName(static::COMMAND)
            ->setDescription('Send daily or weekly report about orders of yesterday')
            ->addOption(
                static::DAILY,
                null,
                InputOption::VALUE_NONE,
                'If set, send daily report'
            )
            ->addOption(
                static::WEEKLY,
                null,
                InputOption::VALUE_NONE,
                'If set, send weekly report'
            )
            ->addOption(
                static::FORCE_EMAIL,
                null,
                InputOption::VALUE_OPTIONAL,
                'Send emails specifically to this email'
            )
            ->addOption(
                static::NOT_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Execute real command'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // services
        $dailyReport = $this->getContainer()->get('food.daily_report');
        $dailyReport->setOutput($output);
        $dailyReport->setTableHelper($this->getHelper('table'));

        $weeklyReport = $this->getContainer()->get('food.weekly_report');
        $weeklyReport->setOutput($output);
        $weeklyReport->setTableHelper($this->getHelper('table'));

        // our options
        $daily = $input->getOption(static::DAILY);
        $weekly = $input->getOption(static::WEEKLY);
        $forceEmail = $input->getOption(static::FORCE_EMAIL);
        $notDryRun = $input->getOption(static::NOT_DRY_RUN);

        if (!(!empty($daily) || !empty($weekly))) {
            $message = sprintf('Please specify either --%s or --%s options',
                               static::DAILY,
                               static::WEEKLY);
            throw new \Exception($message);
        }

        if ($daily) {
            $result = $dailyReport->sendDailyReport($forceEmail, $notDryRun);
            list($error, $text) = $result;
        } elseif ($weekly) {
            $result = $weeklyReport->sendWeeklyReport($forceEmail, $notDryRun);
            list($error, $text) = $result;
        }

        // finally
        $output->writeln($text);

        if ($error) {
            return 2;
        }

        return 0;
    }
}
