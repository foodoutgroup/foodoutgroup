<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MonthlyDriverReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:report:monthly_driver')
            ->setDescription('Send last moths order report groupped by driver')
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'If set, debug information will be logged'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Email won`t be send. Just pure output'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $orderService = $this->getContainer()->get('food.order');
            $translator = $this->getContainer()->get('translator');

            $orders = $orderService->getDriversMonthlyOrderCount();

            $mailer = $this->getContainer()->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->getParameter('title').': '.$translator->trans('general.email.driver_monthly_report'))
                ->setFrom('info@'.$this->getContainer()->getParameter('domain'))
            ;
            $accountingEmail = $this->getContainer()->getParameter('accounting_email');
            $message->addTo($accountingEmail);

            $message->setBody($this->getContainer()->get('templating')
                ->render(
                    'FoodOrderBundle:Command:accounting_monthly_driver_report.html.twig',
                    array(
                        'orders' => $orders,
                        'reportFor' => date("Y-m", strtotime('-1 month')),
                    )
                ), 'text/html');

            $mailer->send($message);

            $output->writeln('Message sent to: '.$accountingEmail);
        } catch (\Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}