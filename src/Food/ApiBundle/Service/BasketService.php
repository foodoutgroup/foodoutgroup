<?php
namespace Food\ApiBundle\Service;

use Food\ApiBundle\Common\ShoppingBasket;
use Food\ApiBundle\Common\ShoppingBasketItem;
use Food\ApiBundle\Entity\ShoppingBasketRelation;
use Food\ApiBundle\Exceptions\ApiException;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
use Symfony\Component\DependencyInjection\ContainerAware;
use Food\ApiBundle\Common\JsonRequest;

class BasketService extends ContainerAware
{
    /**
     * @param JsonRequest $request
     * @return array|null
     */
    public function createBasketFromRequest(JsonRequest $request)
    {
        return $this->_createBasket($request->get('restaurant_id'), $request);
    }

    /**
     * @param JsonRequest $request
     * @return array
     */
    public function _getCreateBasketItems(JsonRequest $request)
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

    /**
     * @param int $id
     * @param JsonRequest $request
     * @param bool $remove
     * @return array|null
     * @throws ApiException
     */
    public function updateBasketFromRequest($id, JsonRequest $request, $remove = false)
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
        $cartService->setNewSessionId($basket->getSession());
        foreach ($request->get('items') as $item) {
            $options = array();
            if (isset($item['options']) && !empty($item['options'])) {
                foreach ($item['options'] as $opt) {
                    $options[] = $dc->getRepository('FoodDishesBundle:DishOption')->find($opt['option_id']);
                }
            }
            $dish = $dc->getRepository('FoodDishesBundle:Dish')->find(intval($item['item_id']));
            $cartService->addDish(
                $dish,
                $dc->getRepository('FoodDishesBundle:DishSize')->find(intval($item['size_id'])),
                $item['count'],
                $options,//$item['options'] // @todo,
                (isset($item['additional_info'])) ? $item['additional_info'] : '',
                $basket->getSession()
            );
        }

        $cartService->recalculateBundles($dish->getPlace()->getId());

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

    /**
     * @param ShoppingBasketRelation $basket
     * @param Cart $cartItem
     */
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

    /**
     * @param int $restaurantId
     * @param JsonRequest $request
     * @return array|null
     */
    private function _createBasket($restaurantId, JsonRequest $request)
    {
        $sessionId = $this->container->get('session')->getId();
        $newBasketRel = new ShoppingBasketRelation();

        $newBasketRel->setSession($sessionId)
            ->setPlaceId($this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($restaurantId));

        $this->container->get('doctrine')->getManager()->persist($newBasketRel);
        $this->container->get('doctrine')->getManager()->flush();

        return $this->updateBasketFromRequest($newBasketRel->getId(), $request, true);
    }

    /**
     * @param int $id
     * @return array|null
     * @throws ApiException
     */
    public function getBasket($id)
    {
        $items = array();
        $basketInfo = $this->container->get('doctrine')->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));

        if (!$basketInfo) {
            throw new ApiException(
                'Basket Not Found',
                401,
                array(
                    'error' => 'Basket Not Found',
                    'description' => ''
                )
            );
        }
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
        "currency": "EUR"
        },
         */
        $basket = new ShoppingBasket();
        $basket->set('basket_id', $basketInfo->getId());
        $basket->set('restaurant_id', $basketInfo->getPlaceId()->getId());
        $basket->set(
            'payment_options',
            array(
                'cash' => true,
                'credit_card' => $basketInfo->getPlaceId()->getCardOnDelivery(),
            )
        );

        $basket->set('expires', (date("U") + (3600 * 24 * 7)));

        /** NEW */

        $basket->set('payment', []);
        $basket->set('payment_method', [
            ["name" => "[#cash#]", "code" => "local", "url" => null],
            ["name" => "[#credit.card#]", "code" => "local.card", "url" => null]
        ]);

        $total = $this->container->get('food.cart')->getCartTotalApi($cartItems, $basketInfo->getPlaceId()) * 100;
        $discount = 0;
        if ($basketInfo->getPlaceId()->getDiscountPricesEnabled()) {
            $discount = ($this->container->get('food.cart')->getCartTotalOld($cartItems, $basketInfo->getPlaceId()) * 100) - $total;
        }

        $basket->set(
            'total_price',
            array(
                'amount' => $total,
                'discount' => $discount < 0.005 ? 0 : $discount,
                'currency' => $this->container->getParameter('currency_iso')
            )
        );
        $basket->set('items', $items);
        return $basket->getData();
    }

    /**
     * @param $id
     * @param $basket_item_id
     * @param JsonRequest $request
     * @throws ApiException
     */
    public function deleteBasketItem($id, $basket_item_id, JsonRequest $request)
    {
        $doc = $this->container->get('doctrine');
        $ent = $doc->getManager()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));
        if (!$ent) {
            throw new ApiException(
                'Basket not found',
                404,
                array(
                    'error' => 'Basket not found',
                    'description' => ''
                )
            );
        }
        $itemInCart = $doc->getManager()->getRepository('FoodCartBundle:Cart')->findOneBy(
            array(
                'cart_id' => $basket_item_id,
                'session' => $ent->getSession(),
                'place_id' => $ent->getPlaceId()
            )
        );
        if (!$itemInCart) {
            throw new ApiException(
                'Item not found',
                404,
                array(
                    'error' => 'Item not found',
                    'description' => ''
                )
            );
        }
        $this->_removeItem($ent, $itemInCart);
    }

    /**
     * @param $id
     * @param $basket_item_id
     * @param JsonRequest $request
     * @throws \Exception
     */
    public function updateBasketItem($id, $basket_item_id, JsonRequest $request)
    {
        $doc = $this->container->get('doctrine');
        $basket = $doc->getManager()->getRepository('FoodApiBundle:ShoppingBasketRelation')->find(intval($id));
        $itemInCart = $doc->getManager()->getRepository('FoodCartBundle:Cart')->findOneBy(
            array(
                'cart_id'=> $basket_item_id,
                'place_id' => $basket->getPlaceId(),
                'session' => $basket->getSession()
            )
        );

        if (!$itemInCart instanceof Cart) {
            throw new \Exception('Cart not found in API. Cart id: '.$basket_item_id.' Place id: '.$basket->getPlaceId());
        }

        $oldOptions = $doc->getRepository('FoodCartBundle:CartOption')->findBy(
            array(
                'session' => $basket->getSession(),
                'cart_id' => $basket_item_id,
                'dish_id' => $itemInCart->getDishId()
            )
        );
        foreach ($oldOptions as $opt) {
            $doc->getManager()->remove($opt);
            $doc->getManager()->flush();
        }
        $newOptions = $request->get('options', array());
        foreach ($newOptions as $optNew) {
            $cartOpt = new CartOption();
            $cartOpt->setCartId($itemInCart->getCartId())
                ->setDishId($itemInCart->getDishId())
                ->setSession($basket->getSession())
                ->setDishOptionId($doc->getRepository('FoodDishesBundle:DishOption')->find($optNew['option_id']));
            $doc->getManager()->persist($cartOpt);
            $doc->getManager()->flush();
        }

        $itemInCart->setQuantity($request->get('count'));
        $dishSize = $doc->getManager()->getRepository('FoodDishesBundle:DishSize')->find($request->get('size_id'));
        $itemInCart->setDishSizeId($dishSize);
        $itemInCart->setComment($request->get('additional_info'));
        $doc->getManager()->persist($itemInCart);
        $doc->getManager()->flush();
    }
}
