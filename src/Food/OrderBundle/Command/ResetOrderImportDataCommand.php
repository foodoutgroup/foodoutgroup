<?php

namespace Food\OrderBundle\Command;

use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ResetOrderImportDataCommand extends ContainerAwareCommand
{

    private $em;
    private $orderDataImportService;

    protected function configure()
    {
        $this
            ->setName('order:data-import:reset')
            ->setDescription('Reset order data import by import id')
            ->addArgument(
                'import_id',
                InputArgument::REQUIRED,
                'Import ID'
            )
            ->addArgument(
                'fieldname',
                InputArgument::REQUIRED,
                'fieldname'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->orderDataImportService = $this->getContainer()->get('food.order_data_import_service');
        $importData = $this->em->getRepository('FoodOrderBundle:OrderDataImport')->find($input->getArgument('import_id'));

        foreach (json_decode($importData->getInfodata(), true) as $ordersData) {
            foreach ($ordersData as $orderData) {
                if ($orderData['fieldname'] == $input->getArgument('fieldname')) {
                    /**
                     * @var $order Order
                     */
                    $realOrder = $this->em->getRepository('FoodOrderBundle:Order')->find($orderData['order']);
                    $newValue = $orderData['old_value'];
                    switch ($orderData['fieldname']) {
                        case 'sf_number':
                            $realOrder->setSfSeries($newValue);
                            break;
                        case 'order_date':
                            $realOrder->setOrderDate($newValue);
                            break;
                        case 'place_id':
                            $realOrder->setPlace($this->em->getRepository('FoodDishesBundle:Place')->find($newValue));
                            break;
                        case 'driver_id':
                            $realOrder->setDriver($this->em->getRepository('FoodAppBundle:Driver')->find($newValue));
                            break;
                        case 'payment_method':
                            $realOrder->setPaymentMethod($newValue);
                            break;
                        case 'payment_method_code':
                            $realOrder->setPaymentMethodCode($newValue);
                            break;
                        case 'delivery_price':
                            $realOrder->setDeliveryPrice($newValue);
                            break;
                        case 'total':
                            $realOrder->setTotal($newValue);
                            break;
                        case 'discount_sum':
                            $realOrder->setDiscountSum($newValue);
                            break;
                    }
                    $this->em->persist($realOrder);

                    $this->orderDataImportService->setImportObject($importData);
                    $this->orderDataImportService->logChange($realOrder, $orderData['fieldname'], $orderData['new_value'], $orderData['old_value']);

                    $this->em->flush();


                }
            }

        }
    }
}