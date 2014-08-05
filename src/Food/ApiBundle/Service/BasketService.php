<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\ShoppingBasket;
use Food\ApiBundle\Common\ShoppingBasketItem;
use Food\ApiBundle\Entity\ShoppingBasketRelation;
use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class BasketService extends ContainerAware
{
    public function createBasketFromRequest(Request $request)
    {
        return $this->_createBasket($request->get('restaurant_id'), $request);
    }

    public function _getCreateBasketItems(Request $request)
    {
        $returner = array();
        $items = $request->get('items', array());
        if (empty($items) && !is_array($items)) {
            $items = array();
        }
        foreach ( $items as $item) {
            $it = new ShoppingBasketItem(null, $this->container);
            $tmpItem =  $it->populateFromCreateRequest($item);

            $returner[] = $tmpItem;
        }
        return $returner;
    }

    public function updateBasketFromRequest($id, Request $request, $remove = false)
    {
        $dc = $this->container->get('doctrine');
        $basket = $dc->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));


        if ($remove) {
            $items1 = $dc->getRepository('FoodCartBundle:CartOption')->findBy(
                array(
                    'session' => $basket->getSession()
                )
            );

            $items2 = $dc->getRepository('FoodCartBundle:Cart')->findBy(
                array(
                    'session' => $basket->getSession()
                )
            );

            foreach ($items1 as $itm) {
                $dc->getManager()->remove($itm);
            }
            foreach ($items2 as $itm) {
                $dc->getManager()->remove($itm);
            }
            $dc->getManager()->flush();
            $dc->getManager()->clear();
        }

        $cartService = $this->container->get('food.cart');

        foreach ($request->get('items') as $item) {
            $options = array();
            if (isset($item['options']) && !empty($item['options'])) {
                foreach ($item['options'] as $opt) {
                    $options[] = $dc->getRepository('FoodDishesBundle:DishOption')->find($opt['option_id']);
                }
            }
            $cartService->addDish(
                $dc->getRepository('FoodDishesBundle:Dish')->find(intval($item['item_id'])),
                $dc->getRepository('FoodDishesBundle:DishSize')->find(intval($item['size_id'])),
                $item['count'],
                $options,//$item['options'] // @todo,
                $item['additional_info'],
                $basket->getSession()
            );
        }
        return $this->getBasket($id);
    }

    /**
     * @param $id
     */
    public function deleteBasket($id)
    {
        $doc = $this->container->get('doctrine');
        $ent = $doc->getManager()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));

        $itemsInCart = $doc->getManager()->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'session' => $ent->getSession(),
                'place_id' => $ent->getPlaceId()
            )
        );

        foreach ($itemsInCart as $itemToRemove) {
           $this->_removeItem($ent, $itemToRemove);
        }

        $doc->getManager()->remove($ent);
        $doc->getManager()->flush();
    }

    private function _removeItem(ShoppingBasketRelation $basket, Cart $cartItem)
    {
        $doc = $this->container->get('doctrine');
        $optionsInCart = $doc->getManager()->getRepository('FoodCartBundle:CartOption')->findBy(
            array(
                'cart_id' => $cartItem->getCartId(),
                'session' => $cartItem->getSession()
            )
        );
        foreach ($optionsInCart as $optionToRemove) {
            $doc->getManager()->remove($optionToRemove);
            $doc->getManager()->flush();
        }
        $doc->getManager()->remove($cartItem);
        $doc->getManager()->flush();
    }

    private function _createBasket($restaurantId, Request $request)
    {
        $sessionId = $this->container->get('session')->getId();
        $newBasketRel = new ShoppingBasketRelation();
        $newBasketRel->setSession($sessionId)
            ->setPlaceId($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($restaurantId));

        $this->container->get('doctrine')->getManager()->persist($newBasketRel);
        $this->container->get('doctrine')->getManager()->flush();

        return $this->updateBasketFromRequest($newBasketRel->getId(), $request, true);
    }

    public function getBasket($id)
    {
        $items = array();
        $basketInfo = $this->container->get('doctrine')->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));
        $cartItems = $this->container->get('doctrine')->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'place_id' => $basketInfo->getPlaceId(),
                'session' => $basketInfo->getSession()
            )
        );
        foreach ($cartItems as $cartItem) {
            $basketItem = new ShoppingBasketItem(null, $this->container);
            $items[] = $basketItem->loadFromEntity($cartItem);
        }
        /**
        "basket_id": 1,
        "restaurant_id": 1,
        "expires": 1400170980,
        "payment_options": {
        "cash": true,
        "credit_card": false
        },
        "total_price": {
        "amount": 3500,
        "currency": "LTL"
        },
         */
        $basket = new ShoppingBasket();
        $basket->set('basket_id', $basketInfo->getId());
        $basket->set('restaurant_id', $basketInfo->getPlaceId()->getId());
        $basket->set('expires', (date("U") + (3600 * 24 * 7)));
        $basket->set(
            'total_price',
            array(
                'amount' => $this->container->get('food.cart')->getCartTotal($cartItems, $basketInfo->getPlaceId()) * 100,
                'currency' => 'LTL'
            )
        );
        $basket->set('items', $items);
        return $basket->getData();
    }

    public function deleteBasketItem($id, $basket_item_id, Request $request)
    {
        $ent = $doc->getManager()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));
        $itemInCart = $doc->getManager()->getRepository('FoodCartBundle:Cart')->find($basket_item_id);
        $this->_removeItem($ent, $itemInCart);
    }

    public function updateBasketItem($id, $basket_item_id, Request $request)
    {

    }
}