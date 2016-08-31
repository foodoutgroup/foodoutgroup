<?php

namespace Food\OrderBundle\Service\NavService;

use Doctrine\DBAL\LockMode;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderAccData;
use Food\OrderBundle\Service\NavService\OrderDataForNav;

trait OrderDataForNavDecorator
{
    public function getOrderDataForNav(Order $order)
    {
        $orderService = $this->container->get('food.order');

        $order = \Maybe($order);
        $driver = $order->getDriver();
        $user = $order->getUser();
        $address = $order->getAddressId();
        $place = $order->getPlace();

        // values for convenience
        $vat = $order->getVat()->val(0.0);
        $total = $order->getTotal()->val(0.0);
        $discountTotal = $order->getDiscountSum()->val(0.0);
        $deliveryTotal = $order->getDeliveryPrice()->val(0.0);
        $foodTotal = $total - $discountTotal - $deliveryTotal;

        // ok so now we fill this handy data structure, nothing special
        $data = new OrderDataForNav();
        $data->id = (int)$order->getId()->val(0);
        $data->date = $order->getOrderDate()->format('Y-m-d')->val('1754-01-01');
        $data->time = '1754-01-01 ' . $order->getOrderDate()->format('H:i:s')->val('00:00:00');
        $data->deliveryDate = $order->getDeliveryTime()->format('Y-m-d')->val('1754-01-01');
        $data->deliveryTime = '1754-01-01 ' . $order->getDeliveryTime()->format('H:i:s')->val('00:00:00');
        $data->staff = 'auto';
        $data->chain = '';
        $data->restaurant = $this->cleanChars($order->getPlaceName()->val(''));
        $data->restaurantAddress = $this->cleanChars($order->getPlacePointAddress()->val(''));
        $data->driver = $this->cleanChars($driver->getName()->val(''));
        $data->deliveryType = $order->getDeliveryType()->val('');
        $data->clientName = sprintf("%s %s",
            $this->cleanChars($user->getFirstname()->val('')),
            $this->cleanChars($user->getLastname()->val('')));
        $data->isDelivered = in_array($order->getOrderStatus()->val(''), [$orderService::$status_completed, $orderService::$status_canceled_produced]) ? 'yes' : 'no';
        $data->deliveryAddress = $this->cleanChars($address->getAddress()->val(''));
        $data->city = $address->getCity()->val('');
        $data->country = '';
        $data->paymentType = $order->getPaymentMethod()->val('');
        $data->foodAmount = (double)$foodTotal;
        $data->foodAmountEUR = (double)$foodTotal;
        $data->foodVAT = (double)$vat;
        $data->drinksAmount = 0.0;
        $data->drinksAmountEUR = 0.0;
        $data->drinksVAT = 0.0;
        $data->alcoholAmount = 0.0;
        $data->alcoholAmountEUR = 0.0;
        $data->alcoholVAT = 0.0;
        $data->deliveryAmount = (double)($order->getDeliveryType()
            ->val('') == 'pickup' ?
            0.0 :
            $deliveryTotal);
        $data->deliveryAmountEUR = (double)$data->deliveryAmount;
        $data->deliveryVAT = (double)$vat;
        $data->giftCardAmount = 0.0;
        $data->giftCardAmountEUR = 0.0;
        $data->discountType = '';
        $data->discountAmount = (double)$discountTotal;
        $data->discountAmountEUR = (double)$discountTotal;
        $data->discountPercent = (double)($total > 0.0 ? ($discountTotal / $total) : 0.0);
        $data->totalAmount = (double)$total;
        $data->totalAmountEUR = (double)$total;

        return $data;
    }

    public function getOrderDataForNavLocally($orderId)
    {
        // services
        $em = $this->container->get('doctrine.orm.entity_manager');

        $orderAccData = $this->findOrderAccData($orderId);
        $order = $em->getRepository('FoodOrderBundle:Order')->find($orderAccData->getOrderId());

        $data = new OrderDataForNav();
        $data->id = $orderAccData->getOrderId();
        $data->date = \Maybe($orderAccData)->getDate()
            ->val('1754-01-01')
        ;
        $data->time = \Maybe($orderAccData)->getTime()
            ->val('1754-01-01 00:00:00')
        ;
        $data->deliveryDate = \Maybe($orderAccData)->getDeliveryDate()
            ->val('1754-01-01')
        ;
        $data->deliveryTime = \Maybe($orderAccData)->getDeliveryTime()
            ->val('1754-01-01 00:00:00')
        ;
        $data->staff = $orderAccData->getStaff();
        $data->chain = $orderAccData->getChain();
        $data->restaurant = $orderAccData->getRestaurant();
        $data->restaurantAddress = $orderAccData->getRestaurantAddress();
        $data->driver = $orderAccData->getDriver();
        $data->deliveryType = $orderAccData->getDeliveryType();
        $data->clientName = $orderAccData->getClientName();
        $data->isDelivered = $orderAccData->getIsDelivered() ? 'yes' : 'no';
        $data->deliveryAddress = $orderAccData->getDeliveryAddress();
        $data->city = $orderAccData->getCity();
        $data->country = $orderAccData->getCountry();
        $data->paymentType = $orderAccData->getPaymentType();
        $data->foodAmount = (double)$orderAccData->getFoodAmount();
        $data->foodAmountEUR = (double)$orderAccData->getFoodAmountEur();
        $data->foodVAT = (double)$orderAccData->getFoodVat();
        $data->drinksAmount = (double)$orderAccData->getDrinksAmount();
        $data->drinksAmountEUR = (double)$orderAccData->getDrinksAmountEur();
        $data->drinksVAT = (double)$orderAccData->getDrinksVat();
        $data->alcoholAmount = (double)$orderAccData->getAlcoholAmount();
        $data->alcoholAmountEUR = (double)$orderAccData->getAlcoholAmountEur();
        $data->alcoholVAT = (double)$orderAccData->getAlcoholVat();
        $data->deliveryAmount = (double)$orderAccData->getDeliveryAmount();
        $data->deliveryAmountEUR = (double)$orderAccData->getDeliveryAmountEur();
        $data->deliveryVAT = (double)$orderAccData->getDeliveryVat();
        $data->giftCardAmount = (double)$orderAccData->getGiftCardAmount();
        $data->giftCardAmountEUR = (double)$orderAccData->getGiftCardAmountEur();
        $data->discountType = $orderAccData->getDiscountType();
        $data->discountAmount = (double)$orderAccData->getDiscountAmount();
        $data->discountAmountEUR = (double)$orderAccData->getDiscountAmountEur();
        $data->discountPercent = (double)$orderAccData->getDiscountPercent();
        $data->totalAmount = (double)$orderAccData->getTotalAmount();
        $data->totalAmountEUR = (double)$orderAccData->getTotalAmountEur();
        $data->sFNumber = $order->getSfSeries() . "" . $order->getSfNumber();
        $data->productionPointAddress = $orderAccData->getRestaurantAddress();
        if ($order->getPlacePoint()) {
            $data->productionPointCode = $order->getPlacePoint()->getCompanyCode();
        } else {
            $this->container->get('logger')->error('Order id: ' . $order->getId() . ' has no place point');
        }

        return $data;
    }

    public function insertOrder(OrderDataForNav $data)
    {
        $conn = $this->initSqlConn();

        if (empty($conn)) return false;

        $query = $this->constructInsertOrderQuery($data);
        $logger = $this->container->get('logger');
        $logger->debug('--- NAV INSERT DATA ---');
        $logger->debug(var_export($data, true));
        $logger->debug('--- NAV INSERT QUERY ---');
        $logger->debug($query);
        $success = $conn->query($query);
        $logger->debug('--- NAV INSERT RESULT ---');
        $logger->debug(var_export($success, true));

        return false === $success ? false : true;
    }

    public function updateOrder(OrderDataForNav $data)
    {
        $conn = $this->initSqlConn();

        if (empty($conn)) return false;

        $query = $this->constructUpdateOrderQuery($data);
        $logger = $this->container->get('logger');
        $logger->debug('--- NAV UPDATE DATA ---');
        $logger->debug(var_export($data, true));
        $logger->debug('--- NAV UPDATE QUERY ---');
        $logger->debug($query);
        $success = $conn->query($query);
        $logger->debug('--- NAV UPDATE RESULT ---');
        $logger->debug(var_export($success, true));

        return false === $success ? false : true;
    }

    /**
     * Touch (insert or update) OrderAccData.
     */
    public function touchOrderAccData(Order $order, OrderDataForNav $data)
    {
        // if order is not completed - cancel
        if (!$this->isCompleted($order)) return;

        // if order is, for example, unsaved - cancel
        if (is_null($order->getId())) return;

        // services
        $em = $this->container->get('doctrine.orm.entity_manager');

        // check if OrderAccData in question already exists
        $maybeDataExists = \Maybe(
            $em->getRepository('FoodOrderBundle:OrderAccData')
                ->findBy(['order_id' => $order->getId()])
        );

        $orderAccData = $maybeDataExists[0]->map(function ($row) use ($em) {
            if ($row->is_none()) {
                // if not - return new entity
                return new OrderAccData();
            } else {
                // if exists - mark unsynced and return it
                $row->setIsSynced(false);

                return $row;
            }
        })->val()
        ;

        $orderAccData
            ->setOrderId($order->getId())
            ->setDate($data->date)
            ->setTime($data->time)
            ->setDeliveryDate($data->deliveryDate)
            ->setDeliveryTime($data->deliveryTime)
            ->setStaff($data->staff)
            ->setChain($data->chain)
            ->setRestaurant($data->restaurant)
            ->setRestaurantAddress($data->restaurantAddress)
            ->setDriver($data->driver)
            ->setDeliveryType($data->deliveryType)
            ->setClientName($data->clientName)
            ->setIsDelivered($data->isDelivered == 'yes' ? true : false)
            ->setDeliveryAddress($data->deliveryAddress)
            ->setCity($data->city)
            ->setCountry($data->country)
            ->setPaymentType($data->paymentType)
            ->setFoodAmount($data->foodAmount)
            ->setFoodAmountEur($data->foodAmountEUR)
            ->setFoodVat($data->foodVAT)
            ->setDrinksAmount($data->drinksAmount)
            ->setDrinksAmountEur($data->drinksAmountEUR)
            ->setDrinksVat($data->drinksVAT)
            ->setAlcoholAmount($data->alcoholAmount)
            ->setAlcoholAmountEur($data->alcoholAmountEUR)
            ->setAlcoholVat($data->alcoholVAT)
            ->setDeliveryAmount($data->deliveryAmount)
            ->setDeliveryAmountEur($data->deliveryAmountEUR)
            ->setDeliveryVat($data->deliveryVAT)
            ->setGiftCardAmount($data->giftCardAmount)
            ->setGiftCardAmountEur($data->giftCardAmountEUR)
            ->setDiscountType($data->discountType)
            ->setDiscountAmount($data->discountAmount)
            ->setDiscountAmountEur($data->discountAmountEUR)
            ->setDiscountPercent($data->discountPercent)
            ->setTotalAmount($data->totalAmount)
            ->setTotalAmountEur($data->totalAmountEUR)
            ->setIsSynced(false)
            ->setSyncTimestamp(null)
        ;

        $em->persist($orderAccData);
        $em->flush();
    }

    public function getUnsyncedOrderData()
    {
        return $this->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('FoodOrderBundle:OrderAccData')
            ->findBy(['is_synced' => 0])
            ;
    }

    protected function constructInsertOrderQuery(OrderDataForNav $data)
    {
        $query = sprintf('INSERT INTO %s %s SELECT %s FROM %s',
            $this->getOrderTableName(),
            sprintf(
                "([%s], [ReplicationCounter])",
                implode('], [', $this->getOrderFieldNames())),
            sprintf(
                "'%s', ISNULL(MAX(ReplicationCounter),0) + 1",
                implode("', '", $this->getOrderValues($data))),
            $this->getOrderTableName());

        return $query;
    }

    protected function constructUpdateOrderQuery(OrderDataForNav $data)
    {
        $fields = $this->getOrderFieldNames();
        $values = $this->getOrderValues($data);

        // we dont need order field & value, for now
        $idField = array_shift($fields);
        $idValue = array_shift($values);

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
            $this->getOrderTableName(),
            implode(', ', $valuesForUpdate) . ', [ReplicationCounter] = ' . $this->getReplicationValueForSql(),
            sprintf('[%s] = %s', $idField, $idValue));

        return $query;
    }

    protected function getOrderTableName()
    {
        // return '[prototipas6].[dbo].[PROTOTIPAS$FoodOut Order]';
        //return $this->orderTable;
        return $this->container->get('food.nav')->getOrderTable();
    }

    protected function getOrderFieldNames()
    {
        return ['Order ID',
            'Order Date',
            'Order Time',
            'Delivery Date',
            'Delivery Time',
            'Staff Description',
            'Chain',
            'Restaurant',
            'Restaurant Address',
            'Driver',
            'Delivery Type',
            'Client Name',
            'Delivered',
            'Delivery Address',
            'City',
            'Country',
            'Payment Type',
            'Food Amount',
            'Food Amount EUR',
            'Food VAT',
            'Drinks Amount',
            'Drinks Amount EUR',
            'Drinks VAT',
            'Alcohol Amount',
            'Alcohol Amount EUR',
            'Alcohol VAT',
            'Delivery Amount',
            'Delivery Amount EUR',
            'Delivery VAT',
            'Gift Card Amount',
            'Gift Card Amount EUR',
            'Discount Type',
            'Discount Amount',
            'Discount Amount EUR',
            'Discount Percent',
            'Total Amount',
            'Total Amount EUR',
            'SF number',
            'Production Point Address',
            'Production Point Code'
        ];
    }

    protected function getOrderValues(OrderDataForNav $data)
    {
        $result = [$data->id,
            $data->date,
            $data->time,
            $data->deliveryDate,
            $data->deliveryTime,
            $data->staff,
            $data->chain,
            $data->restaurant,
            $data->restaurantAddress,
            $data->driver,
            $data->deliveryType,
            $data->clientName,
            $data->isDelivered,
            $data->deliveryAddress,
            $data->city,
            $data->country,
            $data->paymentType,
            $data->foodAmount,
            $data->foodAmountEUR,
            $data->foodVAT,
            $data->drinksAmount,
            $data->drinksAmountEUR,
            $data->drinksVAT,
            $data->alcoholAmount,
            $data->alcoholAmountEUR,
            $data->alcoholVAT,
            $data->deliveryAmount,
            $data->deliveryAmountEUR,
            $data->deliveryVAT,
            $data->giftCardAmount,
            $data->giftCardAmountEUR,
            $data->discountType,
            $data->discountAmount,
            $data->discountAmountEUR,
            $data->discountPercent,
            $data->totalAmount,
            $data->totalAmountEUR,
            $data->sFNumber,
            $data->productionPointAddress,
            $data->productionPointCode

        ];

        return $this->escapeSingleQuotes($result);
    }

    protected function escapeSingleQuotes(array $data)
    {
        return array_map(
            function ($val) {
                return str_replace("'", "", $val);
            },
            $data);
    }

    protected function findOrderAccData($orderId)
    {
        $rows = $this->container
            ->get('doctrine.orm.entity_manager')
            ->getRepository('FoodOrderBundle:OrderAccData')
            ->findBy(['order_id' => $orderId])
        ;

        $maybeRows = \Maybe($rows);

        return $maybeRows[0]->val();
    }

    protected function isCompleted(Order $order)
    {
        $orderService = $this->container->get('food.order');

        return $order->getPaymentStatus() ==
        $orderService::$paymentStatusComplete ? true : false;

    }

    protected function getReplicationValueForSql()
    {
        return '(SELECT ISNULL(MAX(ReplicationCounter),0) FROM ' . $this->getOrderTableName() . ') + 1';
    }

    public function orderExists($id)
    {
        $conn = $this->initSqlConn();

        if (empty($conn)) return false;

        $query = sprintf('SELECT [Order id] FROM %s WHERE [Order id] = %s',
            $this->getOrderTableName(),
            $id);
        $resource = $conn->query($query);
        $row = mssql_fetch_array($resource);
        mssql_free_result($resource);

        return !empty($row) ? true : false;
    }

    /**
     * Cleans up russian characters from a string.
     *
     * @param  string $value
     *
     * @return string
     */
    public function cleanChars($value)
    {
        $language = $this->container->get('food.app.utils.language');

        $newValue = $language->removeChars('ru', $value, false);

        return $newValue;
    }
}
