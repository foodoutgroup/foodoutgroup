<?php
namespace Food\OrderBundle\Command;

use Food\OrderBundle\Entity\OrderDataImport;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrderDataImportCommand extends ContainerAwareCommand
{
    private $em;
    private $orderDataImportService;

    protected function configure()
    {
        $this
            ->setName('order:data-import:import')
            ->setDescription('Start order data import [by import id]')
            ->addArgument(
                'import_id',
                InputArgument::OPTIONAL,
                'Import ID'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $this->orderDataImportService = $this->getContainer()->get('food.order_data_import_service');

            $importId = $input->getArgument('import_id');
            if (!is_null($importId)) {
                $importArray = $this->em->getRepository('FoodOrderBundle:OrderDataImport')->findBy(['id' => $importId]);
            } else {
                $importArray = $this->em->getRepository('FoodOrderBundle:OrderDataImport')->findBy(['isImported' => false]);
            }

            /**
             * @var OrderDataImport $importData
             */
            foreach ($importArray as $importData) {
                $now = new \DateTime();
                $importData->setDate($now);

                $importData->setIsImported(true);
                $rootPath = $this->getContainer()->get('kernel')->getRootDir();
                $changeLog = $this->getContainer()->get('food.order_data_import_service')
                    ->importDataFromFile($rootPath.'/../web/'.OrderDataImport::SERVER_PATH_TO_FILE_FOLDER.'/'.$importData->getFilename(), $importData);
                $importData->setInfodata(json_encode($changeLog['infodata']));
                foreach ($changeLog['orders'] as $order) {
                    $importData->getOrdersChanged()->add($this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($order));
                }
                $this->em->flush($importData);
                $this->em->persist($importData);
            }


        } catch (\Exception $e) {
            $output->writeln('Error: '.$e->getMessage());
            throw $e;
        }
    }
}
