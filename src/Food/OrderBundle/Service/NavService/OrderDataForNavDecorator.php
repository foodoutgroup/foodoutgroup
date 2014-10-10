<?php

namespace Food\OrderBundle\Service\NavService;

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\NavService\OrderDataForNav;
use Pirminis\Maybe;

trait OrderDataForNavDecorator
{
    public function getOrderDataForNav(Order $order)
    {
        $maybeOrder = new Maybe($order);

        $data = new OrderDataForNav();
        $data->id = (int) $order->getId();
        $data->date = $order->getOrderDate()->format('Y-m-d');
        $data->time = $order->getOrderDate()->format('H:i:s');
        $data->deliveryDate = $order->getDeliveryTime()->format('Y-m-d');
        $data->deliveryTime = $order->getDeliveryTime()->format('Y-m-d');
        $data->staff = 'auto';
        $data->chain = '';
        $data->restaurant = $maybeOrder->getPlaceName()->value();
        $data->restaurantAddress = $maybeOrder->getPlacePointAddress()->value();
        $data->driver = $maybeOrder->getDriver()->getName()->value();
        $data->deliveryType = $maybeOrder->getDeliveryType()->value();
        $data->clientName = sprintf("%s %s",
                                    $maybeOrder->getUser()
                                               ->getFirstname()
                                               ->value(),
                                    $maybeOrder->getUser()
                                               ->getLastname()
                                               ->value());
        $data->isDelivered = $maybeOrder->getDeliveryTime()->value() == '' ?
                             'no' :
                             'yes';
        $data->deliveryAddress = $maybeOrder->getAddress()
                                            ->getAddress()
                                            ->value();
        $data->city = $maybeOrder->getAddress()
                                 ->getCity()
                                 ->value();
        $data->country = '';
        $data->paymentType = '';
        $data->foodAmount = 0.0;
        $data->foodAmountEUR = 0.0;
        $data->foodVAT = 0.0;
        $data->drinksAmount = 0.0;
        $data->drinksAmountEUR = 0.0;
        $data->drinksVAT = 0.0;
        $data->alcoholAmount = 0.0;
        $data->alcoholAmountEUR = 0.0;
        $data->alcoholVAT = 0.0;
        $data->deliveryAmount = 0.0;
        $data->deliveryAmountEUR = 0.0;
        $data->deliveryVAT = 0.0;
        $data->giftCardAmount = 0.0;
        $data->giftCardAmountEUR = 0.0;
        $data->discountType = '';
        $data->discountAmount = 0.0;
        $data->discountAmountEUR = 0.0;
        $data->discountPercent = 0.0;
        $data->totalAmount = 0.0;
        $data->totalAmountEUR = 0.0;
        return $data;
    }

    protected function FunctionName($value='')
    {
        # code...
    }
}
