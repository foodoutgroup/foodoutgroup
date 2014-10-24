<?php

namespace Food\OrderBundle\Service\NavService;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\NavService\OrderDataForNav;

trait OrderDataForNavDecorator
{
    public function getOrderDataForNav(Order $order)
    {
        // services. we only need 'misc' service for converting totals to euros
        $misc = $this->container->get('food.app.utils.misc');

        $originalOrder = $order;

        $order = \Maybe($order);
        $driver = $order->getDriver();
        $user = $order->getUser();
        $address = $order->getAddressId();
        $place = $order->getPlace();

        // values for convenience
        $vat = $order->getVat()->val(0.0);
        $total = $order->getTotal()->val(0.0);
        $discountTotal = $order->getDiscountSum()->val(0.0);
        $deliveryTotal = $place->getDeliveryPrice()->val(0.0);
        $foodTotal = $total - $discountTotal - $deliveryTotal;

        // ok so now we fill this handy data structure, nothing special
        $data = new OrderDataForNav();
        $data->id = (int) $order->getId()->val(0);
        $data->date = $order->getOrderDate()->format('Y-m-d')->val('');
        $data->time = $order->getOrderDate()->format('H:i:s')->val('');
        $data->deliveryDate = $order->getDeliveryTime()->format('Y-m-d')->val('');
        $data->deliveryTime = $order->getDeliveryTime()->format('H:i:s')->val('');
        $data->staff = 'auto';
        $data->chain = '';
        $data->restaurant = $order->getPlaceName()->val('');
        $data->restaurantAddress = $order->getPlacePointAddress()->val('');
        $data->driver = $driver->getName()->val('');
        $data->deliveryType = $order->getDeliveryType()->val('');
        $data->clientName = sprintf("%s %s",
                                    $user->getFirstname()->val(''),
                                    $user->getLastname()->val(''));
        $data->isDelivered = $order->getDeliveryTime()->val('') == '' ?
                             'no' :
                             'yes';
        $data->deliveryAddress = $address->getAddress()->val('');
        $data->city = $address->getCity()->val('');
        $data->country = '';
        $data->paymentType = $order->getPaymentMethod()->val('');
        $data->foodAmount = (double) $foodTotal;
        $data->foodAmountEUR = (double) $misc->getEuro($foodTotal);
        $data->foodVAT = (double) $vat;
        $data->drinksAmount = 0.0;
        $data->drinksAmountEUR = 0.0;
        $data->drinksVAT = 0.0;
        $data->alcoholAmount = 0.0;
        $data->alcoholAmountEUR = 0.0;
        $data->alcoholVAT = 0.0;
        $data->deliveryAmount = (double) $deliveryTotal;
        $data->deliveryAmountEUR = (double) $misc->getEuro($deliveryTotal);
        $data->deliveryVAT = (double) $vat;
        $data->giftCardAmount = 0.0;
        $data->giftCardAmountEUR = 0.0;
        $data->discountType = '';
        $data->discountAmount = (double) $discountTotal;
        $data->discountAmountEUR = (double) $misc->getEuro($discountTotal);
        $data->discountPercent = (double) ($discountTotal / $total);
        $data->totalAmount = (double) $total;
        $data->totalAmountEUR = (double) $misc->getEuro($total);

        return $data;
    }

    public function insertOrder(OrderDataForNav $data)
    {
        $query = $this->constructInsertOrderQuery($data);

        // try to connect and execute a query, else return false
        $conn = \Maybe($this->initTestSqlConn());

        return $conn->map(function($conn) use ($query) {
            $isConnected = $conn->val();
            return \Maybe($isConnected ? $conn->query($query) : $isConnected);
        })->val();
    }

    protected function constructInsertOrderQuery(OrderDataForNav $data)
    {
        $query = sprintf('INSERT INTO %s %s VALUES %s',
                         $this->getOrderTableName(),
                         sprintf(
                            "([%s])",
                            implode('], [', $this->getOrderFieldNames())),
                         sprintf(
                            "('%s')",
                            implode("', '", $this->getOrderValues($data))));
        return $query;
    }

    protected function getOrderTableName()
    {
        return '[prototipas6].[dbo].[PROTOTIPAS$FoodOut Order]';
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
                'Total Amount EUR'];
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
                   $data->totalAmountEUR];
        return $this->escapeSingleQuotes($result);
    }

    protected function escapeSingleQuotes(array $data)
    {
        return array_map(
            function($val) { return str_replace("'", "\\'", $val); },
            $data);
    }
}
