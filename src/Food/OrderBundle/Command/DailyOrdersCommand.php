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
                'force-email',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, email from config will be ignored and given will be used'
            )
            ->addOption(
                'force-date',
                null,
                InputOption::VALUE_OPTIONAL,
                'If set, the report will be generated for a given date'
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
            $translator = $this->getContainer()->get('translator');
            $mailer = $this->getContainer()->get('mailer');
            $em = $this->getContainer()->get('doctrine')->getManager();

            $date = null;
            if ($input->getOption('force-date')) {
                $date = $input->getOption('force-date');
            } else {
                $date = date("Y-m-d", strtotime('-1 day'));
            }
            $orders = $em->getRepository('FoodOrderBundle:Order')->getYesterdayOrdersGrouped($date);

            $message = \Swift_Message::newInstance()
                ->setSubject($this->getContainer()->getParameter('title').': '.$translator->trans('general.email.accounting_yesterday_report'))
                ->setFrom('info@'.$this->getContainer()->getParameter('domain'));

            if ($input->getOption('force-email')) {
                $email = $input->getOption('force-email');
            } else {
                $email = $this->getContainer()->getParameter('accounting_email');
            }

            $message->addTo($email);

            $message->setBody($this->getContainer()->get('templating')
                ->render(
                    'FoodOrderBundle:Command:accounting_yesterday_report.html.twig',
                    array(
                        'orders' => $orders,
                        'reportFor' => $date,
                    )
                ), 'text/html');

            // Dont send if dry-run
            if (!$input->getOption('dry-run')) {
                $mailer->send($message);
            }

            $output->writeln('Report generated for date: '.$date);
            $output->writeln('Message sent to: '.$email);
        } catch (\Exception $e) {
            $output->writeln('Error generating report');
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}