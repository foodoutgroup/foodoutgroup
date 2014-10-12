<?php

namespace Food\OrderBundle\Service\NavService;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\NavService\OrderDataForNav;
use Pirminis\Maybe;

trait OrderDataForNavDecorator
{
    public function getOrderDataForNav(Order $order)
    {
        // services. we only need 'misc' service for converting totals to euros
        $misc = $this->container->get('food.app.utils.misc');

        // monads, although we only need $maybeOrder, others are for convenience
        $maybeOrder = new Maybe($order);
        $maybeDriver = $maybeOrder->getDriver();
        $maybeUser = $maybeOrder->getUser();
        $maybeAddress = $maybeOrder->getAddress();
        $maybePlace = $maybeOrder->getPlace();

        // values for convenience
        $vat = (double) (1.0 + $maybeOrder->getVat()->value(0.0) / 100.0);
        $total = $maybeOrder->getTotal()->value(0.0);
        $discountTotal = $maybeOrder->getTotal()->value(0.0);
        $deliveryTotal = $maybePlace->getDeliveryPrice(0.0);
        $foodTotal = $total - $discountTotal - $deliveryTotal;

        // ok so now we fill this handy data structure, nothing special
        $data = new OrderDataForNav();
        $data->id = (int) $maybeOrder->getId()->value(0);
        $data->date = $maybeOrder->getOrderDate()->format('Y-m-d')->value();
        $data->time = $maybeOrder->getOrderDate()->format('H:i:s')->value();
        $data->deliveryDate = $maybeOrder->getDeliveryTime()
                                         ->format('Y-m-d')
                                         ->value();
        $data->deliveryTime = $maybeOrder->getDeliveryTime()
                                         ->format('Y-m-d')
                                         ->value();
        $data->staff = 'auto';
        $data->chain = '';
        $data->restaurant = $maybeOrder->getPlaceName()->value();
        $data->restaurantAddress = $maybeOrder->getPlacePointAddress()->value();
        $data->driver = $maybeDriver->getName()->value();
        $data->deliveryType = $maybeOrder->getDeliveryType()->value();
        $data->clientName = sprintf("%s %s",
                                    $maybeUser->getFirstname()->value(),
                                    $maybeUser->getLastname()->value());
        $data->isDelivered = $maybeOrder->getDeliveryTime()->value() == '' ?
                             'no' :
                             'yes';
        $data->deliveryAddress = $maybeAddress->getAddress()->value();
        $data->city = $maybeAddress->getCity()->value();
        $data->country = '';
        $data->paymentType = $maybeOrder->getPaymentMethod()->value();
        $data->foodAmount = (double) $foodTotal;
        $data->foodAmountEUR = (double) $misc->getEuro($foodTotal);
        $data->foodVAT = (double) ($foodTotal / $vat);
        $data->drinksAmount = 0.0;
        $data->drinksAmountEUR = 0.0;
        $data->drinksVAT = 0.0;
        $data->alcoholAmount = 0.0;
        $data->alcoholAmountEUR = 0.0;
        $data->alcoholVAT = 0.0;
        $data->deliveryAmount = (double) $deliveryTotal;
        $data->deliveryAmountEUR = (double) $misc->getEuro($deliveryTotal);
        $data->deliveryVAT = (double) ($deliveryTotal / $vat);
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
}

// order 982:
//     total: 85.9
//     dish_total: 80.9
//     delivery_price: 5.0
