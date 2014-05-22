<?php
namespace Food\OrderBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DailyOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:report:accounting_email')
            ->setDescription('Send yesterdays order report to accounting')
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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $orderService = $this->getContainer()->get('food.order');
            $translator = $this->getContainer()->get('translator');

            $orders = $orderService->getYesterdayOrdersGrouped();


            $mailer = $this->getContainer()->get('mailer');

            $message = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->getParameter('title').': '.$translator->trans('general.email.accounting_yesterday_report'))
                ->setFrom('info@'.$this->getContainer()->getParameter('domain'))
            ;

            $message->addTo($this->getContainer()->getParameter('accounting_email'));

            $message->setBody($this->getContainer()->get('templating')
                ->render(
                    'FoodOrderBundle:Command:accounting_yesterday_report.html.twig',
                    array(
                        'orders' => $orders,
                        'reportFor' => date("Y-m-d", strtotime('-1 day')),
                    )
                ));

            $mailer->send($message);

            $output->writeln('Message sent to: '.$this->getContainer()->getParameter('accounting_email'));
        } catch (Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}