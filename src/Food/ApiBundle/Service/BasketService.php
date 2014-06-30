<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\ShoppingBasketItem;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class BasketService extends ContainerAware
{
    public function createBasketFromRequest(Request $request)
    {
        $data = array();
        return $this->_createBasket($data);
    }

    private function _createBasket($data)
    {
        return $this;
    }

    public function getBasket($id)
    {
        $returner = array();
        $basketInfo = $this->container->get('doctrine')->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));
        $cartItems = $this->container->get('doctrine')->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'place_id' => $basketInfo->getPlaceId(),
                'session' => $basketInfo->getSession()
            )
        );
        foreach ($cartItems as $cartItem) {
            $basketItem = new ShoppingBasketItem(null, $this->container);
            $returner[] = $basketItem->loadFromEntity($cartItem);
        }
        return $returner;
    }
}