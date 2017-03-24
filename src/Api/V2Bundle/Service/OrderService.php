<?php

namespace Api\V2Bundle\Service;

use Api\BaseBundle\Common\JsonRequest;
use Api\BaseBundle\Exceptions\ApiException;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\DishSize;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\OrderBundle\Entity\LoyaltyCard;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderDetails;
use Food\OrderBundle\Entity\OrderDetailsOptions;
use Food\OrderBundle\Entity\OrderExtra;
use Food\OrderBundle\Service\OrderService as FO;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Component\HttpFoundation\Request;

class OrderService extends \Food\ApiBundle\Service\OrderService
{

    /**
     * @param JsonRequest $json
     */
    public function createOrderFromRequest(Place $place, JsonRequest $json)
    {

        $em = $this->container->get('doctrine')->getManager();
        $doctrine = $this->container->get('doctrine');


        $deliveryType = ($json->has('type') && $json->get('type', 'deliver') == 'pickup' ? 'pickup' : 'deliver');
        $os = $this->container->get('food.order');

        if (!$json->has('address') && $deliveryType == 'delivery') {
            throw new ApiException('delivery required parameter address to be set');
        } elseif (!$json->has('placepoint') && $deliveryType == "pickup") {
            throw new ApiException('pickup required placepoint address to be set');
        }

        if (!$json->has('customer')) {
            throw new ApiException('parameter customer required');
        }

        $customer = $json->get('customer');

        if (!isset($customer['firstname']) || !isset($customer['phone'])) {
            throw new ApiException('customer required parameters firstname and phone (lastname and email is optional)');
        }

        $um = $this->container->get('fos_user.user_manager');
        if(empty($customer['email'])) {
            $customer['email'] = $customer['phone'].'@foodout.lt';
        }

        $order = new Order();
        $location = false;
        if ($deliveryType == "pickup" && $json->has('placepoint')) {
            $placePoint = $this->container->get("api.v2.place")->getPlacePoint($json->get('placepoint'));
            $order->setPaymentMethod("local");
            $order->setDeliveryPrice(0);

        } else {
            $address = $json->get('address', []);
            if (!isset($address['city']) || !isset($address['street']) || !isset($address['house_number'])) {
                throw new ApiException('Address  must have city, street and house_number parameters (flat_number - optional)'); // todo
            }

            $order->setPaymentMethod("local.card");
            $addressBuffer = $address['street'] . ' ' . $address['house_number'] . (!empty($address['flat_number']) ? '-' . $address['flat_number'] . '' : '');
            $location = $this->container->get('food.googlegis')->groupData($addressBuffer, $address['city']);
            $id = $doctrine->getRepository('FoodDishesBundle:Place')->getPlacePointNearWithDistance($place->getId(), $location, false, true);
            $placePoint = $doctrine->getRepository('FoodDishesBundle:PlacePoint')->find($id);
            $dp = $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->getDeliveryPriceForPlacePoint($place, $placePoint, $location);
            $order->setDeliveryPrice($dp);

        }

        $deliveryTotal = $deliveryPrice = $order->getDeliveryPrice();

        $order->setSource("APIv2");
        $order->setPlace($place);
        $order->setPlaceName($place->getName());
        $order->setPlacePointSelfDelivery($place->getSelfDelivery());
        $order->setPlacePoint($placePoint);
        $order->setPlacePointCity($placePoint->getCity());
        $order->setPlacePointAddress($placePoint->getAddress());
        if ($this->container->get('food.zavalas_service')->isZavalasTurnedOnByCity($placePoint->getCity())) {
            $order->setDuringZavalas(true);
        }
        $order->setOrderDate(new \DateTime("now"));

        $orderDate = ($json->has('preorder') ? $json->get("preorder") : null);

        if (empty($orderDate)) {
            $miscService = $this->container->get('food.app.utils.misc');
            $placeService = $this->container->get('food.places');

            $timeShift = $miscService->parseTimeToMinutes($placeService->getDeliveryTime($place));

            if (empty($timeShift) || $timeShift <= 0) {
                $timeShift = 60;
            }

            $deliveryTime = new \DateTime("now");
            $deliveryTime->modify("+" . $timeShift . " minutes");
        } else {
            $deliveryTime = new \DateTime($orderDate);
        }

        $order->setDeliveryTime($deliveryTime);
        $order->setDeliveryPrice($deliveryTotal); // todo
        $order->setVat($this->container->getParameter('vat'));
        $order->setOrderHash(
            $os->generateOrderHash($order)
        );


        /**
         * @var User $user
         */
        $user = $um->findUserByEmail($customer['email']);

        if(!$user) {
            $user = $um->createUser();

            if(isset($customer['company'])) {

                $company = $customer['company'];
                if(isset($company['title'])) {
                    $user->setCompanyName($company['title']);
                }
                if(isset($company['code'])) {
                    $user->setCompanyCode($company['code']);
                }

                if(isset($company['address'])) {
                    $user->setCompanyAddress($company['address']);
                }
            }
            $user->setEmail($customer['email']);
            $user->setUsername($customer['email']);
            $user->setFirstname($customer['firstname']);
            $user->setLastname($customer['lastname']);
            $user->setPhone($customer['phone']);
            $user->addRole('ROLE_USER');
            $user->setEnabled(true);
            $user->setPassword(md5(time()));
            $user->setFullyRegistered(true);
            $em->persist($user);

            if($location !== false) {
                $address = new UserAddress();
                $address->setCity($location['city']);
                $address->setUser($user);
                $address->setLat($location['lat']);
                $address->setLon($location['lng']);
                $address->setAddress($location['address_orig']);
                $address->setDefault(true);
                $em->persist($address);
            }

        } else {

            if($location !== false) {
                $address = $em->getRepository('FoodUserBundle:UserAddress')->findOneBy(['user' => $user->getId(), 'address' => $location['address_orig']]);
                if (!$address) {
                    $address = new UserAddress();
                    $address->setCity($location['city']);
                    $address->setUser($user);
                    $address->setLat($location['lat']);
                    $address->setLon($location['lng']);
                    $address->setAddress($location['address_orig']);
                    $address->setDefault(true);
                    $em->persist($address);
                }
            }
        }
        if(isset($address)) {
            $order->setAddressId($address);
        }
        $order->setUser($user);

        $order->setUserIp('');

        if ($this->container->get('food.zavalas_service')->isZavalasTurnedOnByCity($order->getPlacePointCity())) {
            $order->setDuringZavalas(true);
        }

        $order->setDeliveryType($deliveryType);
        $order->setLocale($this->container->get('request')->getLocale());
        $order->setOrderStatus(\Food\OrderBundle\Service\OrderService::$status_new);

        if ($json->has('preorder')) {
            $order->setOrderStatus(\Food\OrderBundle\Service\OrderService::$status_preorder)->setPreorder(true);
        } else if (!$json->has('preorder') && $deliveryType == "pickup") {
            $miscService = $this->container->get('food.app.utils.misc');
            $timeShift = $miscService->parseTimeToMinutes($place->getPickupTime());
            if (empty($timeShift) || $timeShift <= 0) {
                $timeShift = 60;
            }
            $deliveryTime = new \DateTime("now");
            $order->setDeliveryTime($deliveryTime->modify("+" . $timeShift . " minutes"));
        }


        if (!$json->has('items') || count($json->get('items')) < 1) {
            throw new ApiException('items parameter required with some products');
        }

        $productCollection = [];
        foreach ($json->get('items') as $item) {
            $product = [];
            if (!isset($item['code']) || !isset($item['count'])) {
                throw new ApiException("All products must have code and count attributes");
            }

            $prod = $doctrine->getRepository('FoodDishesBundle:DishSize')->findDishSizeByCodeAndPlace($item['code'], $place);
            if (!$prod) {
                throw new ApiException('Dish with code ' . $item['code'] . ' was not found');
            }

            $product['object'] = $prod;
            $product['count'] = (int)$item['count'];
            $product['additional'] = [];
            if (isset($item['additional'])) {
                foreach ($item['additional'] as $additional) {
                    if (!isset($additional['code']) || !isset($additional['count'])) {
                        throw new ApiException("All products additionals must have code and count attributes");
                    }

                    $option = $doctrine->getRepository('FoodDishesBundle:DishOption')->findOneBy([
                        'place' => $place->getId(),
                        'code' => $additional['code']
                    ]);

                    if (!$option) {
                        throw new ApiException('Dish option with code ' . $item['code'] . ' was not found');
                    }

                }
            } else {
                $product['additional'] = [];
            }

            $productCollection[] = $product;
        }

        $order->setTotalBeforeDiscount($this->getProductTotal($productCollection));

        $discount = false;
        if ($json->has('discount')) {

            $dis = $json->get('discount');

            if (!isset($dis['method'])) {
                throw new ApiException('discount parameter method is required');
            }

            switch ($dis['method']) {
                case "card":

                    if (!isset($dis['code'])) {
                        throw new ApiException('discount parameter code is required (parameter type is optional)');
                    }
                    $discount = $this->container->get('api.v2.loyalty_card')->validate($place, $dis['code'], (isset($dis['type']) ? $dis['type'] : null));
                    if($discount['success']) {
                        $lc = new LoyaltyCard();
                        $lc->setOrder($order);
                        $lc->setDescription($discount['message']);
                        $lc->setTitle($discount['title']);
                        $lc->setDiscount($discount['discount']);
                        $lc->setPlace($place);
                        $lc->setCreatedAt(new \DateTime());
                        $em->persist($lc);
                    }

                    break;
                default:
                    throw new ApiException('discount method ' . $dis['method'] . ' was not found');
                    break;
            }
        }

        $orderExtra = new OrderExtra();
        $orderExtra->setOrder($order);
        $orderExtra->setMetaData($_SERVER['HTTP_USER_AGENT']);
        $orderExtra->setFirstname($customer['firstname']);
        $orderExtra->setPhone($customer['phone']);

        if (isset($customer['lastname'])) {
            $orderExtra->setLastname($customer['lastname']);
        }

        if (isset($customer['email'])) {
            $orderExtra->setEmail($customer['email']);
        }

        $order->setOrderExtra($orderExtra);

        $discountPercent = 0;
        $discountSumLeft = 0;
        $discountSumTotal = 0;
        $discountUsed = 0;
        $sumTotal = 0;
//        $deiveryPrice = 0; // todo get delivery price :)
        if ($discount) {
            $discountPercent = $discount['discount'];
            $discountSumLeft = $discountSumTotal = $this->getDiscountTotal($productCollection, $discountPercent);
            $order
                ->setDiscountSum($discountSumLeft)
                ->setDiscountSize($discountPercent);

        }
        $relationPart = $discountSumLeft / $order->getTotalBeforeDiscount();

        foreach ($productCollection as $product) {
            /**
             * @var DishSize $dishSize
             */
            $dishSize = $product['object'];

            $priceBeforeDiscount = $dishSize->getPrice();
            $discountPercentForInsert = 0;

            $price = $dishSize->getCurrentPrice();
            if (!$this->isDishSizeAlcohol($dishSize)) {
                if ($priceBeforeDiscount == $price && $discountPercent > 0) { // todo? :::................... $priceBeforeDiscount == $price
                    $price = round($priceBeforeDiscount * ((100 - $discountPercent) / 100), 2);
                    $discountPercentForInsert = $discountPercent;
                } elseif ($discountSumLeft > 0) {

                    $discountPart = (float)round($price * $product['count'] * $relationPart * 100, 2) / 100;
                    if ($discountPart < $discountSumLeft) {
                        $discountSum = $discountPart;
                    } else {
                        if ($discountUsed + $discountPart > $discountSumTotal) {
                            $discountSum = $discountSumTotal - $discountUsed;
                        } else {
                            $discountSum = $discountSumLeft;
                        }
                    }
                    $discountSum = (float)round($discountSum / $product['count'] * 100, 2) / 100;
                    $priceForInsert = $price - $discountSum;
                    $discountSumLeft -= $discountSum;
                    $discountUsed += $discountSum;
                    $price = $priceForInsert;
                }
            }


            $dish = new OrderDetails();
            $dish->setDishId($dishSize->getDish())
                ->setOrderId($order)
                ->setQuantity($product['count'])
                ->setDishSizeCode($dishSize->getCode())
                ->setPrice($price)
                ->setOrigPrice($priceBeforeDiscount)
                ->setPriceBeforeDiscount($priceBeforeDiscount)
                ->setPercentDiscount($discountPercentForInsert)
                ->setDishName($dishSize->getDish()->getName())
                ->setDishUnitId($dishSize->getUnit()->getId())
                ->setDishUnitName($dishSize->getUnit()->getName())
                ->setDishSizeCode($dishSize->getCode())
                ->setIsFree(false);
            $em->persist($dish);

            $sumTotal += $product['count'] * $price;

            $dishOptionPricesBeforeDiscount = $this->container->get('food.dishes')->getDishOptionsPrices($dishSize->getDish());

            foreach ($product['additional'] as $opt) {
                /**
                 * @var DishOption $attribute
                 */
                $attribute = $opt['object'];

                if (isset($dishOptionPricesBeforeDiscount[$dishSize->getId()][$attribute->getId()])) {
                    $dishOptionPricesBeforeDiscount = $dishOptionPricesBeforeDiscount[$dishSize->getId()][$attribute->getId()];
                } else {
                    $dishOptionPricesBeforeDiscount = $attribute->getPrice();
                }

                $dishOptionPrice = $dishOptionPricesBeforeDiscount;

                if ($discountSumLeft > 0) {

                    $discountPart = (float)round($dishOptionPrice * $opt['count'] * $relationPart * 100, 2) / 100;
                    if ($discountPart < $discountSumLeft) {
                        $discountSum = $discountPart;
                    } else {
                        if ($discountUsed + $discountPart > $discountSumTotal) {
                            $discountSum = $discountSumTotal - $discountUsed;
                        } else {
                            $discountSum = $discountSumLeft;
                        }
                    }
                    $discountSum = (float)round($discountSum / $opt['count'] * 100, 2) / 100;
                    $priceForInsert = $dishOptionPrice - $discountSum;
                    $discountSumLeft -= $discountSum;
                    $discountUsed += $discountSum;
                    $dishOptionPrice = $priceForInsert;
                }

                $orderOpt = new OrderDetailsOptions();
                $orderOpt->setDishOptionId($attribute)
                    ->setDishOptionCode($attribute->getCode())
                    ->setDishOptionName($attribute->getName())
                    ->setPrice($dishOptionPrice)
                    ->setPriceBeforeDiscount($dishOptionPricesBeforeDiscount)
                    ->setDishId($dishSize->getDish())
                    ->setOrderId($order)
                    ->setQuantity($opt['count'])
                    ->setOrderDetail($dish);
                $em->persist($orderOpt);
                $sumTotal += $opt['count'] * $dishOptionPrice;
            }
        }

        $sumTotal += $deliveryTotal;
        $order->setDeliveryPrice($deliveryPrice)
            ->setTotal($sumTotal);

        $em->persist($order);
        $em->flush();

        return $order->getOrderHash();
    }



    public function getOrderForResponse(Order $order, $list = false)
    {
        $message = $this->getOrderStatusMessage($order);

        $title = $this->convertOrderStatus($order->getOrderStatus());
        if ($title == "pre") {
            $title = "waiting_user_confirmation";
        }

        if (!empty($list)) {
            $total_sum = (($this->getCartService()->getCartTotal($list) * 100));
            $total_sum = $total_sum + ($order->getDeliveryPrice() * 100);
        } else {
            $order_total = ($order->getTotal() * 100);
            if ($order_total > 0) {
                $total_sum = $order_total + ($order->getDiscountSum() * 100);
            } else {
                $total_sum = $order_total;
            }
        }

        // If coupon in use
        $discount = null;
        $coupon = $order->getCoupon();
        if (!empty($coupon)) {
            $discount['discount_sum'] = $order->getDiscountSum() * 100;
            $discount['discount_size'] = $order->getDiscountSize();
            $total_sum_with_discount = $total_sum - ($order->getDiscountSum() * 100);
            if ($total_sum_with_discount < 0) {
                $total_sum_with_discount = 0;
                $total_sum = 0;
                if (!$coupon->getFreeDelivery()) {
                    $total_sum = ($order->getDeliveryPrice() * 100);
                }
            }
            $discount['total_sum_with_discount'] = $total_sum_with_discount;
        }

        $returner = [
            'order_id'    => $order->getId(),
            'order_hash'    => $order->getOrderHash(),
            'total_price' => [
                //'amount' => $order->getTotal() * 100,
                'amount'   => $total_sum,
                'currency' => $this->container->getParameter('currency_iso')
            ],
            'delivery_price' => $order->getDeliveryPrice(),
            'payment_method' => $this->container->get('translator')->trans('mobile.payment.'.$order->getPaymentMethod()),
            'order_date' => $order->getOrderDate()->format('H:i'),
            'discount'    => $discount,
            'state'       => [
                'title'       => $title,
                'message' => $message,
            ],
            'details'     => [
                'code'    => $order->getPlacePoint()->getInternalCode(),
                'restaurant_title' => $order->getPlace()->getName(),
                'items'            => $this->_getItemsForResponse($order)
            ],
            'service'     => $this->_getServiceForResponse($order)
        ];



        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    public function getOrderForResponseFull(Order $order)
    {
        $returner = $this->getOrderForResponse($order);

        if ($order->getAddressId()) {
            $returner['location'] = [
                'from' => [
                    'lat' => $order->getPlacePoint()->getLat(),
                    'lon' => $order->getPlacePoint()->getLon(),
                ],
                'to' => [
                    'lat' => $order->getAddressId()->getLat(),
                    'lon' => $order->getAddressId()->getLon(),
                ]
            ];
        }
        $returner['details']['restaurant_address'] = $order->getPlacePointAddress();
        $returner['details']['items'] = $this->_getItemsForResponseFull($order);
        $returner['service']['delivery_time'] = $order->getDeliveryTime()->format('Y-m-d H:i:s');
        $returner['service']['customer_firstname'] = $order->getOrderExtra()->getFirstname();
        $returner['service']['customer_lastname'] = $order->getOrderExtra()->getLastname();
        $returner['service']['customer_phone'] = $order->getOrderExtra()->getPhone();
        $returner['status'] = $order->getOrderStatus();
        if ($driver = $order->getDriver()) {
            $returner['driver'] = [
                'id' => $driver->getId(),
                'phone' => $driver->getPhone(),
                'name' => $driver->getName(),
            ];
        }

        return $returner;
    }

//    /**
//     * @param Order $order
//     *
//     * @return array
//     */
//    public function changeOrderStatus(Order $order, Request $request = null)
//    {
//        $orderService = $this->container->get('food.order');
//
//        $status = $request->get('status');
//
//        if ($orderService->isValidOrderStatusChange($order->getOrderStatus(), $this->formToEntityStatus($status))) {
//            switch($status) {
//                case 'confirm':
//                    $orderService->statusAccepted('api_v2');
//                    break;
//
//                case 'delay':
//                    $orderService->statusDelayed('api_v2', 'delay reason: '.$request->get('reason'));
//                    $orderService->getOrder()->setDelayed(true);
//
//                    $reason = $request->get('reason');
//                    $duration = $request->get('duration');
//                    if(empty($reason) || empty($duration)) {
//                        throw new ApiException('reason and duration is required');
//                    }
//
//                    $orderService->getOrder()->setDelayReason($reason);
//                    $orderService->getOrder()->setDelayDuration($duration);
//                    $orderService->saveDelay();
//                    break;
//
//                case 'cancel':
//                    $reason = $request->get('reason');
//                    if(empty($reason)) {
//                        throw new ApiException('reason is required');
//                    }
//                    $orderService->getOrder()->setDelayReason($reason);
//
//                    $orderService->statusCanceled('api_v2');
//
//                    break;
//
//                case 'finish':
//                    $orderService->statusFinished('api_v2');
//                    break;
//
//                case 'completed':
//                    $orderService->statusCompleted('api_v2');
//                    break;
//                default:
//                    throw  new ApiException('status not found');
//                    break;
//            }
//
//            $orderService->saveOrder();
//
//            return array('status' => true, "new_status" => $status);
//        } else {
//            $errorMessage = sprintf(
//                'Restoranas %s bande uzsakymui #%d pakeisti uzsakymo statusa is "%s" i "%s"',
//                $orderService->getOrder()->getPlaceName(),
//                $orderService->getOrder()->getId(),
//                $order->getOrderStatus(),
//                $this->formToEntityStatus($status)
//            );
//            $this->container->get('logger')->alert($errorMessage);
//        }
//    }

    /**
     * @param string$formStatus
     * @return string
     */
    public function formToEntityStatus($formStatus)
    {
        $statusTable = array(
            'confirm' => \Food\OrderBundle\Service\OrderService::$status_accepted,
            'delay' => \Food\OrderBundle\Service\OrderService::$status_delayed,
            'cancel' => \Food\OrderBundle\Service\OrderService::$status_canceled,
            'finish' => \Food\OrderBundle\Service\OrderService::$status_finished,
            'partialy_completed' => \Food\OrderBundle\Service\OrderService::$status_partialy_completed,
            'completed' => \Food\OrderBundle\Service\OrderService::$status_completed,
        );

        if (!isset($statusTable[$formStatus])) {
            return '';
        }

        return $statusTable[$formStatus];
    }

    /**
     * @param Order[] $orders
     * @return array
     */
    public function getOrdersForResponseFull($orders, $hash)
    {
        /**
         * @var PlacePoint $placePoint
         */
        $placePoint = $this->container->get('doctrine.orm.entity_manager')
            ->getRepository('FoodDishesBundle:PlacePoint')->findOneBy(['hash' => $hash])
        ;
        if (!empty($placePoint)) {
            $ordersData = [
                'restaurant' => [
                    'title' => $placePoint->getPlace()->getName(),
                    'address' => $placePoint->getAddress(),
                    'logo' => $placePoint->getPlace()->getWebPath()
                ]
            ];
            /**
             * @var Order[] $orders
             */
            foreach ($orders as $order) {
                $ordersData['orders'][] = $order->__toArray();
            }
        } else {
            throw new \Exception('Place point not found.');
        }
        return $ordersData;
    }

    /**
     * @param Order $order
     *
     * @return string
     */
    public function getOrderStatusMessage(Order $order)
    {
        $message = '';

        if ($order->getDelayed()) {
            $message = $this->container->get('translator')->trans(
                'mobile.order_status.order_delayed',
                ['%delayTime%' => $order->getDelayDuration()]
            )
            ;
        }

        return $message;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getItemsForResponse(Order $order)
    {
        $returner = [];
        $currency = $this->container->getParameter('currency_iso');

        foreach ($order->getDetails() as $detail) {
            $this->container->get('doctrine')->getManager()->refresh($detail);
            $sum = 0;
            //$sum+= $detail->getPrice() * $detail->getQuantity();
            if ($detail->getDishId()->getDiscountPricesEnabled() && $order->getPlace()->getDiscountPricesEnabled()) {
                $current_price = $detail->getOrigPrice();
                $sizes = $detail->getDishId()->getSizes();
                foreach ($sizes as $size) {
                    if ($size->getUnit()->getId() == $detail->getDishUnitId()) {
                        $current_price = $size->getCurrentPrice();
                    }
                }
                $sum += $current_price * $detail->getQuantity();
            } else {
                $sum += $detail->getOrigPrice() * $detail->getQuantity(); // egles prasymu rodom orig_price
            }

            $options = [];
            foreach ($detail->getOptions() as $option) {
                $sum += $option->getPrice() * $option->getQuantity();
                if ($option->getDishOptionId()) {
                    $options[] = [
                        'code' => $option->getDishOptionCode(),
                        'price' => [
                            'count' => $option->getQuantity(),
                            'amount' => sprintf("%.0f", ($option->getQuantity() * $option->getPrice() * 100)),
                            'currency' => $currency,
                        ],
                        'title' => $option->getDishOptionName()
                    ];
                }
            }
            $sum = sprintf("%.0f", ($sum * 100));
            $returner[] = [
//                'code' => $detail->getDish,
                'title' => $detail->getDishName(), //.', '.$detail->getDishUnitName(), Po pokalbio su shernu - laikinai skipinam papildoma info.
                'count' => $detail->getQuantity(),
                'options' => $options,
                'price' => [
                    'amount'   => $sum,
                    'currency' => $currency
                ]
            ];
        }

        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getItemsForResponseFull(Order $order)
    {
        $returner = [];
        $currency = $this->container->getParameter('currency_iso');

        foreach ($order->getDetails() as $detail) {
            $sum = 0;
            //$sum+= $detail->getPrice() * $detail->getQuantity();
            if ($detail->getDishId()->getDiscountPricesEnabled() && $order->getPlace()->getDiscountPricesEnabled()) {
                $current_price = $detail->getOrigPrice();
                $sizes = $detail->getDishId()->getSizes();
                foreach ($sizes as $size) {
                    if ($size->getUnit()->getId() == $detail->getDishUnitId()) {
                        $current_price = $size->getCurrentPrice();
                    }
                }
                $sum += $current_price * $detail->getQuantity();
            } else {
                $sum += $detail->getOrigPrice() * $detail->getQuantity(); // egles prasymu rodom orig_price
            }

            foreach ($detail->getOptions() as $option) {
                $sum += $option->getPrice() * $option->getQuantity();
            }
            $sum = sprintf("%.0f", ($sum * 100));
            $options = [];
            foreach ($detail->getOptions() as $option) {
                $options[] = $option->getDishOptionName();
            }
            $returner[] = [
                'code' => $detail->getDishSizeCode(),
                'title' => $detail->getDishName(),
                'unit' => $detail->getDishUnitName(),
                'options' => $options,
                'count' => $detail->getQuantity(),
                'price' => [
                    'amount'   => $sum,
                    'currency' => $currency
                ]
            ];
        }

        return $returner;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function _getServiceForResponse(Order $order)
    {
        $miscUtil = $this->container->get('food.app.utils.misc');

        switch ($order->getDeliveryType()) {
            case FO::$deliveryPickup:
                $deliveryType = 'pickup';
                $parsedAddress = $miscUtil->parseAddress(
                    $order->getPlacePointAddress()
                );
                $time = $order->getPlace()->getPickupTime();
                break;

            case FO::$deliveryDeliver:
            default:
                $deliveryType = 'delivery';
                $parsedAddress = $miscUtil->parseAddress(
                // @TODO check if addressId exists
                    $order->getAddressId()->getAddress()
                );
                $time = $order->getPlace()->getDeliveryTime();
                break;
        }

        $returner = [
            "type"    => $deliveryType,
            "address" => [
                "street"       => $parsedAddress['street'],
                "house_number" => $parsedAddress['house'],
                "flat_number"  => $parsedAddress['flat'],
                "city"         => $order->getPlacePointCity(),
                "comments"     => $order->getComment()
            ],
        ];

        if ($order->getDeliveryType() == FO::$deliveryDeliver) {
            $returner['price'] = [
                //'amount' => $order->getPlace()->getDeliveryPrice()*100,
                'amount'   => $order->getDeliveryPrice() * 100,
                'currency' => $this->container->getParameter('currency_iso'),
            ];
        }

        return $returner;
    }

    /**
     * @param string $status
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function convertOrderStatus($status)
    {
        $statusMap = [
            FO::$status_preorder           => 'preorder',
            FO::$status_nav_problems       => 'accepted',
            FO::$status_new                => 'accepted',
            FO::$status_unapproved         => 'accepted',
            FO::$status_accepted           => 'preparing',
            FO::$status_assiged            => 'preparing',
            FO::$status_forwarded          => 'preparing',
            FO::$status_delayed            => 'delayed',
            FO::$status_completed          => 'completed',
            FO::$status_partialy_completed => 'completed',
            FO::$status_failed             => 'failed',
            FO::$status_finished           => 'prepared',
            FO::$status_canceled           => 'canceled',
            FO::$status_canceled_produced  => 'canceled_produced',
            FO::$status_pre                => 'pre'
        ];

        if (!isset($statusMap[$status])) {
            throw new \InvalidArgumentException('Unknown status: ' . $status);
        }

        return $statusMap[$status];
    }

    private function getProductTotal($productCollection)
    {
        $total = 0;

        foreach ($productCollection as $product) {

            $dishSize = $product['object'];

            $total += ((float)$dishSize->getCurrentPrice() * 100) * (int) $product['count'];

            $dishOptionsPrices = $this->container->get('food.dishes')->getDishOptionsPrices($dishSize->getDish());

            foreach ($product['additional'] as $option) {

                $dishOption = $option['object'];
                if (isset($dishOptionsPrices[$dishSize->getId()][$dishOption->getId()])) {
                    $dishOptionsPrice = (float)$dishOptionsPrices[$dishSize->getId()][$dishOption->getId()];
                } else {
                    $dishOptionsPrice = (float)$dishOption->getPrice();
                }
                $total += ($dishOptionsPrice * 100) * (int) $option['count'];
            }

        }


        return sprintf("%01.2f", $total / 100);
    }

    public function isDishSizeAlcohol(DishSize $dish)
    {
        $dishCategories = $dish->getDish()->getCategories();
        foreach ($dishCategories as $dishCategory) {
            if ($dishCategory->getAlcohol()) {
                return true;
            }
        }
        return false;
    }

    public function getDiscountTotal($productCollection, $discountPercent)
    {
        $total = 0;
        foreach ($productCollection as $product) {
            $thisDishFitsUs = false;
            /**
             * @var DishSize $dishSize
             */
            $dishSize = $product['object'];

            if (!$dishSize->getDish()->getPlace()->getDiscountPricesEnabled()) {
                $thisDishFitsUs = true;
            } elseif (!$dishSize->getDish()->getDiscountPricesEnabled()) {
                $thisDishFitsUs = true;
            } elseif ($dishSize->getDish()->getDiscountPricesEnabled()
                && $dishSize->getDish()->getPlace()->getDiscountPricesEnabled()
                && $dishSize->getDiscountPrice() == 0) {
                $thisDishFitsUs = true;
            }
            if ($dishSize->getDish()->getNoDiscounts()) {
                $thisDishFitsUs = false;
            } else if ($this->isDishSizeAlcohol($dishSize)) {
                $thisDishFitsUs = false;
            }
            $theDish = 0;
            if ($thisDishFitsUs) {
                $theDish += $dishSize->getCurrentPrice() * $product['count'];
                foreach ($product['additional'] as $opt) {
                    $attr = $opt['object'];
                    $theDish += $attr->getPrice() * $opt['count'];
                }
                if ($theDish > 0) {
                    $total += round(($theDish * $discountPercent / 100), 2);
                }
            }
        }
        return $total;
    }

}

