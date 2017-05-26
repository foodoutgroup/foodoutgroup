<?php
namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\Place;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessImportCommand extends ContainerAwareCommand
{
    private $timeStart;
    private $maxChecks = 5;

    protected function configure()
    {
        $this->timeStart = microtime(true);

        $this
            ->setName('import:process')
            ->setDescription('Process content import')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Locale for importing')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Fields for importing')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serviceImp = $this->getContainer()->get('food.import_export_service');
        try {
            $resp = $serviceImp->setLocale($input->getOption('locale'))->doImport();
            if ($resp) {
                $output->writeln('<fg=green>All content was imported.</fg=green>');
            }
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}