<?php
namespace Food\OrderBundle\Command;

use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\Driver;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderExtra;
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
            ->addOption(
                'pause-on-error',
                null,
                InputOption::VALUE_NONE,
                'Make a 5 second pause on error, so error can be read'
            )
            ->addOption(
                'time-shift',
                null,
                InputOption::VALUE_OPTIONAL,
                'Alter how old orders are imported - enter only integer expresion in hours'
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
        $pauseOnError = $input->getOption('pause-on-error');
        $timeShift = $input->getOption('time-shift');
        if (!empty($timeShift)) {
            $timeShift = '-'.$timeShift.' hour';
        } else {
            $timeShift = null;
        }

        try {
            $em = $this->getContainer()->get('doctrine')->getManager();
            $orderService = $this->getContainer()->get('food.order');
            $navService = $this->getContainer()->get('food.nav');
            $miscUtility = $this->getContainer()->get('food.app.utils.misc');
            $userService = $this->getContainer()->get('fos_user.user_manager');
            $gisService = $this->getContainer()->get('food.location');
            $log = $this->getContainer()->get('logger');
            $country = $this->getContainer()->getParameter('country');

            $log->alert("Nav order import beggins ---------");

            $orders = $navService->getNewNonFoodoutOrders($timeShift);
            $stats = array(
                'found' => count($orders),
                'skipped' => 0,
                'error' => 0,
                'processed' => 0,
            );
            $logMessage = 'Found '.$stats['found'].' orders to process';
            $output->writeln($logMessage);
            $log->alert($logMessage);

            if (!empty($orders) && $stats['found'] > 0) {
                foreach ($orders as $orderId => $orderData) {
                    $logMessage = 'Order #'.$orderData['OrderNo'].' - import started';
                    $output->writeln($logMessage);
                    $log->alert($logMessage);
                    $logMessage = 'Data: '."\n".var_export($orderData, true);
                    $output->writeln($logMessage);
                    $log->alert($logMessage);
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
                        $logMessage = 'Order #'.$orderData['OrderNo'].' already exists with id #'.$orderId;
                        $output->writeln($logMessage);
                        $log->alert($logMessage);
                        continue;
                    }

                    // ISC canceled without adding a single dish.. dont process such crap
                    if ($orderData['OrderSum'] == $orderData['DeliveryAmount'] && $orderData['OrderStatus'] == 10) {
                        $output->writeln('SKIPPING - ISC canceled order without even adding dishes');
                        $stats['skipped']++;
                        continue;
                    }

                    $deliveryType = OrderService::$deliveryDeliver;
                    if ($orderData['Sales Type'] == 'H_TAKEOFF') {
                        $deliveryType = OrderService::$deliveryPickup;
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
                        // Dont throw alert if it's only a pickup :D
                        if ($deliveryType != OrderService::$deliveryPickup) {
                            $log->error($skipMessage);
                        }
                        $stats['error']++;
                        if ($pauseOnError) {
                            sleep(5);
                        }
                        continue;
                    }
                    $place = $placePoint->getPlace();
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
                            $orderData['Order Date']->format("Y-m-d")
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
                        // Date Created changed to Order Date
                        // because 01-01 23:30 order => delivers on 01-01 00:30 (past time)
                            date("Y-m-d", strtotime($orderData['Order Date']))
                            .' '
                            .date("H:i:s", strtotime($orderData['Contact Pickup Time']))
                        );
                    }

                    $output->writeln('Order Date: '.$orderDate->format("Y-m-d H:i:s"));
                    $output->writeln('Delivery Date: '.$deliveryDate->format("Y-m-d H:i:s"));

                    // Create if not a dry-run
                    $paymentMethod = "local";
                    if (isset($orderData['Tender Type']) && $orderData['Tender Type'] == 2)
                    {
                        $paymentMethod = "local.card";
                    }
                    if (!$dryRun) {
                        $order->setOrderDate($orderDate)
                            ->setDeliveryTime($deliveryDate)
                            ->setTotal($orderData['OrderSum'])
                            ->setDeliveryPrice($orderData['DeliveryAmount'])
                            ->setDeliveryType($deliveryType)
                            ->setVat($orderData['VAT %'])
                            ->setNavPorcessedOrder(true)
                            ->setNavPriceUpdated(true)
                            ->setLastUpdated(new \DateTime())
                            ->setNavDeliveryOrder($orderId)
                            ->setOrderFromNav(true)
                            ->setSource(Order::SOURCE_NAV)
                            ->setPaymentMethod($paymentMethod)
                            ->setPaymentStatus(OrderService::$paymentStatusComplete)
                            ->setOrderStatus(OrderService::$status_new)
                            ->setLocale($this->getContainer()->getParameter('locale'))
                            ->setComment($orderData['Directions']);
                        //~ ->setComment(iconv('CP1257', 'UTF-8', $orderData['Directions']));


                        // User data
                        $phone = $miscUtility->formatPhone($orderData['Phone No_'], $country);
                        $customerEmail = trim($orderData['CustomerEmail']);


                        $user = null;
                        if (!empty($customerEmail)) {
                            $output->writeln('Searching for user with email: '.$customerEmail);
                            $user = $userService->findUserByEmail($customerEmail);
                        }

                        if (!$user instanceof User || $user->getId() == '') {
                            $output->writeln('Searching for user with phone: ' . $phone);
                            $user = $userService->findUserBy(array('phone' => $phone));
                        }

                        // no user, create one
                        if (!$user instanceof User || $user->getId() == '') {
                            $output->writeln('User not found - creating new user...');
                            $user = $userService->createUser();
                            $user->setUsername($phone);
                            $user->setFirstname($phone);
                            if (!empty($customerEmail)) {
                                $user->setEmail($customerEmail);
                            } else {
                                $user->setEmail($phone . '@foodout.lt');
                            }
                            $user->setPhone($phone);
                            $user->setPassword('temp_'.$phone);
                            $user->setEnabled(true);
                            $user->setRoles(array('ROLE_USER'));

                            $userService->updateUser($user);
                        }

                        // save extra order data to separate table
                        $orderExtra = new OrderExtra();
                        $orderExtra->setOrder($order);

                        $orderExtra->setFirstname($user->getFirstname())
                            ->setLastname($user->getLastname())
                            ->setPhone($user->getPhone())
                            ->setEmail($user->getEmail());

                        $order->setOrderExtra($orderExtra);

                        // if delivery, only then you mess with address
                        if ($deliveryType != OrderService::$deliveryPickup) {
                            // User address data
                            $output->writeln('Order delivery type - deliver. Setting address');
                            $output->writeln('Address from NAV: '.var_export($orderData['Address'], true));
                            //~ $fixedAddress = trim(iconv('CP1257', 'UTF-8', $orderData['Address']));
                            //~ $output->writeln('Converted address: '.var_export($fixedAddress, true));
                            // OMG, kartais NAV adresas turi bruksniukus, kuriu niekam nereikia.. fuj fuj fuj
                            if (mb_strpos($orderData['Address'], '--') == (mb_strlen($orderData['Address']) - 2)) {
                                $output->writeln('Found -- chars.. Cleaning address');
                                $orderData['Address'] = mb_substr($orderData['Address'], 0, (mb_strlen($orderData['Address']) - 2));
                            }
                            if (mb_strpos($orderData['Address'], '--,') !== false) {
                                $output->writeln('Found --, chars.. Cleaning address');
                                $orderData['Address'] = str_replace('--,', ',', $orderData['Address']);
                            }
                            $output->writeln('Cleaned address: '.var_export($orderData['Address'], true));

                            // Format address
                            $fixedCity = $orderData['City'];
                            $fixedCity = mb_convert_case($fixedCity, MB_CASE_TITLE, "UTF-8");

                            if (!$cityObj = $em->getRepository('FoodAppBundle:City')->findOneBy( ['title' => $fixedCity] ))
                            {
                                try {
                                    $cityObj = new City();
                                    $cityObj->setTitle($fixedCity);
                                    $cityObj->setActive(0);
                                    $em->persist($cityObj);
                                    $em->flush();
                                    $output->writeln('City created ' . $fixedCity);

                                } catch (\Exception $e) {
                                    $output->writeln($e->getMessage());
                                }
                            }
                            $output->writeln('Fixed city: '.var_export($fixedCity, true));

                            $addressStr = strstr($orderData['Address'], ', ' . $fixedCity, true);
                            $addressStr = mb_convert_case($addressStr, MB_CASE_TITLE, "UTF-8");
                            $addressStr = str_replace(['G.', 'Pr.'], ['g.', 'pr.'], $addressStr);
                            $output->writeln('Fixed street: '.var_export($addressStr, true));
                            $gisAddress = $gisService->findByAddress($addressStr." ,".$fixedCity);


                            if (!$gisAddress) {
                                $address = $em->getRepository('FoodUserBundle:UserAddress')
                                    ->findOneBy(
                                        array(
                                            'cityId' => $cityObj->getId(),
                                            'address' => $addressStr,
                                            'user' => $user
                                        )
                                    );
                            } else {
                                $address = $em->getRepository('FoodUserBundle:UserAddress')
                                    ->findOneBy(
                                        array(
                                            'cityId' => $gisAddress['city_id'],
                                            'addressId' => $gisAddress['id'],
                                            'user' => $user
                                        )
                                    );
                            }

                            if(!$address) {
                                $address = $em->getRepository('FoodUserBundle:UserAddress')
                                    ->findOneBy(
                                        array(
                                            'city' => $fixedCity,
                                            'address' => $addressStr,
                                            'user' => $user
                                        )
                                    );
                            }



                            if (!$address) {
                                if (empty($gisAddress['city_id'])) {
                                    $gisAddress['city_id'] = $cityObj->getId();
                                }

                                $address = $this->getContainer()
                                    ->get('food.location')->saveAddressFromArrayToUser($gisAddress, $user);

                                $user->addAddress($address);
                                $userService->updateUser($user);
                            }

                            // Set Address in order
                            $order->setAddressId($address);


                            /**
                            cCustomer.[Name] AS CustomerName,
                            cCustomer.[Address] AS CustomerAddress,
                            cCustomer.[City] AS CustomerCity,
                            cCustomer.[VAT Registration No_] AS CustomerVatNo,
                            cCustomer.[E-mail] AS CustomerEmail,
                            cCustomer.[Registration No_] AS CustomerRegNo
                             */
                            if (!empty($orderData['CustomerName']) && !empty($orderData['CustomerRegNo'])) {
                                $addressToSave = $order->getAddressId()->getAddress();
                                $cityToSave = $order->getAddressId()->getCityId()->getTitle();
                                if (!empty($orderData['CustomerAddress'])) {
                                    $addressToSave = trim($orderData['CustomerAddress']);
                                    $addressToSave = mb_convert_case($addressToSave, MB_CASE_TITLE, "UTF-8");
                                    $addressToSave = str_replace(array('G.', 'Pr.'), array('g.', 'pr.'), $addressToSave);
                                }

                                if (!empty($orderData['CustomerCity'])) {
                                    $cityToSave = $orderData['CustomerCity'];
                                    $cityToSave = mb_convert_case($cityToSave, MB_CASE_TITLE, "UTF-8");
                                }

                                $companyAddress = $addressToSave;

                                if (strpos($addressToSave, $cityToSave) === false) {
                                    $companyAddress .= ", ". $cityToSave;
                                }

                                $order->setCompany(true)
                                    ->setCompanyName($orderData['CustomerName'])
                                    ->setCompanyCode(trim($orderData['CustomerRegNo']))
                                    ->setVatCode(trim($orderData['CustomerVatNo']))
                                    ->setCompanyAddress($companyAddress);
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

                        // Paskutiniu metu daug duplikatu issoka be userio ir adreso. Darom papildoma checka, nes useris turi but, o deliveriui - Adresas
                        $testUser = $order->getUser();
                        $testAddress = $order->getAddressId();
                        if (!$testUser->getId() || ($order->getDeliveryType() == OrderService::$deliveryDeliver && !$testAddress->getId())) {
                            $messageNoUser = 'Error importing from NAV. Order with no user and address. Skipping this order';
                            $output->writeln($messageNoUser);
                            $log->alert($messageNoUser);
                            $stats['error']++;
                            continue;
                        }

                        $stats['processed']++;
                        $em->persist($order);
                        // Log order creation method
                        $orderService->logOrder($order, 'create', 'Created from Navision');
                    }

                    $logMessage = 'Order #'.$orderData['OrderNo'].' - import finished'."\n";
                    $output->writeln($logMessage);
                    $log->alert($logMessage);

                    // Protection that cron jobs wont overlap. If we are near 5 minutes in processing - lets kill it. The next cron will continue
                    if ((microtime(true) - $startTime) >= 265) {
                        throw new \Exception('Nav import is taking too long. Overlaping protection. Cron duration'.sprintf('$0.2fs', (microtime(true) - $startTime)));
                    }
                }

                $output->writeln('------------------------------------');
                $output->writeln('          Migration stats');
                $output->writeln('Orders found: '.$stats['found']);
                $output->writeln('Orders skipped (existing): '.$stats['skipped']);
                $output->writeln('Orders with error: '.$stats['error']);
                $output->writeln('Orders processed: '.$stats['processed']);
                $output->writeln(sprintf('Process duration: %0.2fs', (microtime(true) - $startTime)));
                $log->alert(sprintf('[Performance] NAV import process duration: %0.2fs', (microtime(true) - $startTime)));

                // Save all created orders if not a dry run
                if (!$dryRun) {
                    $em->flush();
                    $this->getContainer()->get('doctrine')->getConnection()->close();
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
