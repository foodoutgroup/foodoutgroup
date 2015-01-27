<?php
namespace Food\OrderBundle\Command;

use Food\AppBundle\Entity\Driver;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NavImportOrdersCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('order:nav:import')
            ->setDescription('Import orders from navision')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'No order will be imported. Just pure debug output'
            )
        ;

        mb_internal_encoding('utf-8');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $output->writeln('Dry-run. No inserts will be performed');
        }
        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orderService = $this->getContainer()->get('food.order');
            $navService = $this->getContainer()->get('food.nav');
            $miscUtility = $this->getContainer()->get('food.app.utils.misc');
            $userService = $this->getContainer()->get('fos_user.user_manager');
            $gisService = $this->getContainer()->get('food.googlegis');
            $log = $this->getContainer()->get('logger');
            $country = $this->getContainer()->getParameter('country');

            $orders = $navService->getNewNonFoodoutOrders();

            $stats = array(
                'found' => count($orders),
                'skipped' => 0,
                'error' => 0,
                'processed' => 0,
            );
            $output->writeln('Found '.$stats['found'].' orders to process');
            if (!empty($orders) && $stats['found'] > 0) {
                foreach ($orders as $orderId => $orderData) {
                    $output->writeln('Order #'.$orderData['OrderNo'].' - import started');
                    $output->writeln('Data: '."\n".var_export($orderData, true));
                    $output->writeln('');

                    if ($orderData['OrderStatus'] == 0) {
                        $stats['skipped']++;
                        $output->writeln('Order #'.$orderData['OrderNo'].' skipped - status NEW - we only import from accepted');
                        continue;
                    }

                    if ($orderData['OrderSum'] == 0) {
                        $stats['skipped']++;
                        $output->writeln('Order #'.$orderData['OrderNo'].' skipped - Total sum with VAT is 0');
                        continue;
                    }

                    $localOrder = $orderService->getOrderByNavDeliveryId($orderId);
                    // Order already exists - skip it
                    if ($localOrder instanceof Order) {
                        $stats['skipped']++;
                        $output->writeln('Order #'.$orderData['OrderNo'].' already exists with id #'.$orderId);
                        continue;
                    }

                    // ISC canceled without adding a single dish.. dont process such crap
                    if ($orderData['OrderSum'] == $orderData['DeliveryAmount'] && $orderData['OrderStatus'] == 10) {
                        $output->writeln('SKIPPING - ISC canceled order without even adding dishes');
                        $stats['skipped']++;
                        continue;
                    }

                    $restaurantNo = trim($orderData['Restaurant No_']);
                    $placePoint = $navService->getLocalPlacePoint($orderData['Chain'], $restaurantNo);
                    // Skip if placepoint not found - scream for emergency..
                    if (!$placePoint) {
                        $skipMessage = sprintf(
                            'Cannot find PlacePoint for Nav Delivery Order: "%s" with Chain: "%s" and Restaurant No: "%s"',
                            $orderId,
                            $orderData['Chain'],
                            $restaurantNo
                        );
                        $output->writeln($skipMessage);
                        $log->error($skipMessage);
                        $stats['error']++;
                        continue;
                    }
                    $place = $placePoint->getPlace();;
                    $output->writeln('Place for order: '.$place->getName().' ('.$place->getId().')');
                    $output->writeln('Placepoint for order: '.$placePoint->getAddress().' ('.$placePoint->getId().')'."\n");

                    // Create if not a dry-run
                    if (!$dryRun) {
                        $order = $orderService->createOrder(
                            $placePoint->getPlace()->getId(),
                            $placePoint,
                            true
                        );
                    }

                    $deliveryType = OrderService::$deliveryDeliver;
                    if ($orderData['Sales Type'] == 'H_TAKEOFF') {
                        $deliveryType = OrderService::$deliveryPickup;
                    }
                    $output->writeln('Order total: '.$orderData['Amount Incl_ VAT']);
                    $output->writeln('VAT: '.$orderData['VAT %']);
                    $output->writeln('Delivery Amount: '.$orderData['DeliveryAmount']);
                    $output->writeln('Delivery Type: '.$deliveryType);

                    if ($orderData['Date Created'] instanceof \DateTime) {
                        $orderDate = new \DateTime(
                            $orderData['Date Created']->format("Y-m-d")
                            . ' '
                            . $orderData['Time Created']->format("H:i:s")
                        );

                        $deliveryDate = new \DateTime(
                            $orderData['Date Created']->format("Y-m-d")
                            .' '
                            .$orderData['Contact Pickup Time']->format("H:i:s")
                        );
                    } else {
                        $orderDate = new \DateTime(
                           date("Y-m-d", strtotime($orderData['Date Created']))
                            . ' '
                            .date("H:i:s", strtotime($orderData['Time Created']))
                        );

                        $deliveryDate = new \DateTime(
                            date("Y-m-d", strtotime($orderData['Date Created']))
                            .' '
                            .date("H:i:s", strtotime($orderData['Contact Pickup Time']))
                        );
                    }

                    $output->writeln('Order Date: '.$orderDate->format("Y-m-d H:i:s"));
                    $output->writeln('Delivery Date: '.$deliveryDate->format("Y-m-d H:i:s"));

                    // Create if not a dry-run
                    if (!$dryRun) {
                        $order->setOrderDate($orderDate)
                            ->setTotal($orderData['OrderSum'])
                            ->setDeliveryPrice($orderData['DeliveryAmount'])
                            ->setDeliveryType($deliveryType)
                            ->setVat($orderData['VAT %'])
                            ->setNavPorcessedOrder(true)
                            ->setNavPriceUpdated(true)
                            ->setLastUpdated(new \DateTime('now'))
                            ->setNavDeliveryOrder($orderId)
                            ->setOrderFromNav(true)
                            ->setPaymentMethod('local')
                            ->setPaymentStatus(OrderService::$paymentStatusComplete)
                            ->setOrderStatus(OrderService::$status_new)
                            ->setLocale($this->getContainer()->getParameter('locale'))
                            ->setComment($orderData['Directions']);


                        // User data
                        $phone = $miscUtility->formatPhone($orderData['Phone No_'], $country);
                        $output->writeln('Searching for user with phone: '.$phone);
                        $user = $userService->findUserBy(array('phone' => $phone));

                        // no user, create one
                        if (!$user instanceof User || $user->getId() == '') {
                            $output->writeln('User not found - creating new user...');
                            $user = $userService->createUser();
                            $user->setUsername($phone);
                            $user->setFirstname($phone);
                            $user->setEmail($phone.'@foodout.lt');
                            $user->setPhone($phone);
                            $user->setPassword('temp_'.$phone);
                            $user->setEnabled(true);
                            $user->setRoles(array('ROLE_USER'));

                            $userService->updateUser($user);
                        }

                        // if delivery, only then you mess with address
                        if ($deliveryType != OrderService::$deliveryPickup) {
                            // User address data
                            $fixedAddress = iconv('CP1257', 'UTF-8', $orderData['Address']);
                            $fixedCity = iconv('CP1257', 'UTF-8', $orderData['City']);
                            $addressStr = strstr($fixedAddress, ', ' . $fixedCity, true);
                            $addressData = $gisService->getPlaceData($fixedAddress);
                            $gisService->groupData($addressData, $addressStr, $fixedCity);

                            $address = $em->getRepository('FoodUserBundle:UserAddress')
                                ->findOneBy(
                                    array(
                                        'city' => $fixedCity,
                                        'address' => $addressStr,
                                        'user' => $user
                                    )
                                );

                            if (!$address instanceof UserAddress || $address->getId() == '') {
                                $address = new UserAddress();
                                $address->setUser($user)
                                    ->setCity($fixedCity)
                                    ->setAddress($addressStr)
                                    ->setLat($addressData->results[0]->geometry->location->lat)
                                    ->setLon($addressData->results[0]->geometry->location->lng);
                                $em->persist($address);
                                // Deja sitas ispusins ir orderiu insertus :(
                                $em->flush();

                                $user->addAddress($address);
                                $userService->updateUser($user);
                            }

                            // Set Address in order
                            $order->setAddressId($address);



                            /**
                             *                  cCustomer.[Name] AS CustomerName,
                            cCustomer.[Address] AS CustomerAddress,
                            cCustomer.[City] AS CustomerCity,
                            cCustomer.[VAT Registration No_] AS CustomerVatNo,
                            cCustomer.[E-mail] AS CustomerEmail,
                            cCustomer.[Registration No_] AS CustomerRegNo
                             */
                            if (!empty($orderData['CustomerName']) && !empty($orderData['CustomerRegNo'])) {
                                $addressToSave = $order->getAddressId()->getAddress();
                                $cityToSave = $order->getAddressId()->getCity();
                                if (!empty($orderData['CustomerAddress'])) {
                                    $addressToSave = $orderData['CustomerAddress'];
                                }
                                if (!empty($orderData['CustomerCity'])) {
                                    $cityToSave = $orderData['CustomerCity'];
                                }

                                $order->setCompany(true)
                                    ->setCompanyName($orderData['CustomerName'])
                                    ->setCompanyCode($orderData['CustomerRegNo'])
                                    ->setVatCode($orderData['CustomerVatNo'])
                                    ->setCompanyAddress($addressToSave.", ". $cityToSave);
                            }


                            $driverId = trim($orderData['Driver ID']);
                            if ($orderData['OrderStatus'] > 6 && !empty($driverId)) {
                                $order->setNavDriverCode($driverId);

                                $output->writeln('Searching for possible driver with NAV ID: '.$driverId);
                                // Driver shoud be assigned already to not mess with the data
                                $driver = $navService->getDriverByNavId($driverId);

                                if (!$driver instanceof Driver) {
                                    $missingDriverMaessage = 'Driver with Nav ID '.$driverId.' not found in local DB';
                                    $output->writeln($missingDriverMaessage);
                                    $log->error($missingDriverMaessage);
                                } else {
                                    $output->writeln('Driver found: ID: '.$driver->getId().' Name: '.$driver->getName());
                                    $order->setDriver($driver);
                                    $order->setOrderStatus(OrderService::$status_assiged);
                                }
                            }
                        }

                        $order->setUser($user);

                        $stats['processed']++;
                        $em->persist($order);
                        // Log order creation method
                        $orderService->logOrder($order, 'create', 'Created from Navision');
                    }

                    $output->writeln('Order #'.$orderData['OrderNo'].' - import finished'."\n");
                }

                $output->writeln('------------------------------------');
                $output->writeln('          Migration stats');
                $output->writeln('Orders found: '.$stats['found']);
                $output->writeln('Orders skipped (existing): '.$stats['skipped']);
                $output->writeln('Orders with error: '.$stats['error']);
                $output->writeln('Orders processed: '.$stats['processed']);
                $output->writeln(sprintf('Process duration: %0.2fs', (microtime(true) - $startTime)));

                // Save all created orders if not a dry run
                if (!$dryRun) {
                    $em->flush();
                }
            }
        } catch (\Exception $e) {
            $output->writeln('Error importing orders from Navision');
            $output->writeln('Error: '.$e->getMessage());
            $output->writeln('Trace: ');
            $output->writeln($e->getTraceAsString());
            throw $e;
        }
    }
}
