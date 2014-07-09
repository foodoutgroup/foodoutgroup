<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\ShoppingBasketItem;
use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class BasketService extends ContainerAware
{
    public function createBasketFromRequest(Request $request)
    {
        @mail("paulius@foodout.lt", "Rquest create basket", print_r($request->request->all(), true), "FROM: testas@foodout.lt");
        $data = array(
            'restaurant_id' => $request->get('restaurant_id'),
            'items' => $this->_getCreateBasketItems($request)
        );
        return $this->_createBasket($data);
    }

    public function _getCreateBasketItems(Request $request)
    {
        $returner = array();
        $items = array();
        if (!empty($request->get('items'))) {
            $items = $request->get('items');
        }
        foreach ( $items as $item) {
            $it = new ShoppingBasketItem(null, $this->container);
            $tmpItem =  $it->populateFromCreateRequest($item);

            $returner[] = $tmpItem;
        }
        return $returner;
    }

    public function updateBasketFromRequest($id, Request $request)
    {
        @mail("paulius@foodout.lt", "Update basket", print_r($request->request->all(), true), "FROM: testas@foodout.lt");
        /**
        {
            "restaurant_id": 1,
            "items": [
                {
                "item_id": 2,
                "count": 3,
                "options": [],
                "additional_info": ""
                }
            ]
        }
         */
        $basket = $this->container->get('doctrine')->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(itnval($id));
        foreach ($request->get('items') as $item) {
            $cartItem = new Cart();
            $cartItem->setDishId($item['item_id'])
                ->setComment($item['additional_info'])
                ->setQuantity($item['count'])
                ->setPlaceId($basket->getPlaceId())
                ->setSession($basket->getSession());

            $this->container->get('doctrine')->getManager()->persist($cartItem);
            $this->container->get('doctrine')->getManager()->flush();
        }
        return $this->getBasket($id);
    }

    public function deleteBasket($id)
    {
        $doc = $this->container->get('doctrine');
        $ent = $doc->getManager()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(itnval($id));
        $doc->getManager()->remove($ent);
        $doc->getManager()->flush();
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