<?php

namespace Food\OrderBundle\Command;

use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderAccData;
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
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Execute real thing'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->orderDataImportService = $this->getContainer()->get('food.order_data_import_service');
        $importData = $this->em->getRepository('FoodOrderBundle:OrderDataImport')->find($input->getArgument('import_id'));
        $navService = $this->getContainer()->get('food.nav');

        if (!$importData) {
            throw new \Exception('import not found');
        }

        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $output->writeln('Dry run - nothing will be changed');
        } else {
            $conn = $navService->initSqlConn();
        }

        foreach (json_decode($importData->getInfodata(), true) as $ordersData) {
            foreach ($ordersData as $orderData) {
                if ($orderData['fieldname'] == $input->getArgument('fieldname')) {
                    /**
                     * @var $order Order
                     */
                    $realOrder = $this->em->getRepository('FoodOrderBundle:Order')->find($orderData['order']);

                    $orderAccData = $this->em->getRepository('FoodOrderBundle:OrderAccData')
                        ->findBy(['order_id' => $realOrder->getId()]);

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
                            if ($orderAccData) {
                                $orderAccData = $orderAccData[0];

                                $data = new \stdClass;
                                $data->discountAmount = (double)$realOrder->getDiscountSum();
                                $data->discountAmountEUR = (double)$realOrder->getDiscountSum();
                                $data->discountPercent = (double)($realOrder->getTotal() > 0.0 ? ($realOrder->getDiscountSum() / $realOrder->getTotal()) : 0.0);

                                $orderAccData
                                    ->setDiscountAmount($data->discountAmount)
                                    ->setDiscountAmountEur($data->discountAmountEUR)
                                    ->setDiscountPercent($data->discountPercent);

                                $query = $this->generateNavQuery($orderAccData);

                                if (!$dryRun) {
                                    $conn->query($query);
                                } else {
                                    $output->writeln('Nav query: ' . $query);
                                }

                            }
                            break;
                    }

                    if (!$dryRun) {
                        $this->em->persist($realOrder);
                        $this->orderDataImportService->setImportObject($importData);
                        $this->orderDataImportService->logChange($realOrder, $orderData['fieldname'], $orderData['new_value'], $orderData['old_value']);
                        $this->em->flush();
                    }

                    $output->writeln('OrderId: ' . $realOrder->getId() . ', fieldname: ' . $orderData['fieldname'] . ', from: ' . $orderData['new_value'] . ', to: ' . $orderData['old_value']);
                }
            }

        }
    }

    /**
     * only for discount
     */
    private function generateNavQuery($orderAccData)
    {
        $navService = $this->getContainer()->get('food.nav');

        $fields = [
            'Discount Amount',
            'Discount Amount EUR',
            'Discount Percent'
        ];
        $values = [
            $orderAccData->getDiscountAmount(),
            $orderAccData->getDiscountAmountEur(),
            $orderAccData->getDiscountPercent()
        ];

        // format fields
        $fieldsCallback = function ($val) {
            return sprintf('[%s]', $val);
        };
        $fields = array_map($fieldsCallback, $fields);

        // format values
        $valuesCallback = function ($val) {
            // return is_numeric($val) ? $val : sprintf("'%s'", $val);
            return sprintf("'%s'", $val);
        };
        $values = array_map($valuesCallback, $values);

        // combine fields with values
        $combined = array_combine($fields, $values);

        // generate final useful array with combined fields and values
        $valuesForUpdate = [];
        foreach ($combined as $key => $value) {
            $valuesForUpdate[] = sprintf('%s = %s', $key, $value);
        }

        // create query
        $query = sprintf('UPDATE %s SET %s WHERE %s',
            $navService->getOrderTable(),
            implode(', ', $valuesForUpdate) . ', [ReplicationCounter] = ' . $navService->getReplicationValueForSql(),
            sprintf('[%s] = %s', 'Order ID', $orderAccData->getOrderId()));

        return $query;
    }
}
