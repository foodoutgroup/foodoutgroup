<?php

namespace Food\CartBundle\Service;

use Food\DishesBundle\Entity\Dish;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\DishSize;
use Food\DishesBundle\Entity\Place;


class CartService {

    private $container;
    /**
     * @var  \Doctrine\Common\Persistence\ObjectManager
     */
    private $em;

    public $newSessionId = null;

    public function __construct()
    {

    }


    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     *
     * @return $this
     */
    public function setEm($em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEm()
    {
        if (empty($this->em)) {
            $this->setEm($this->getContainer()->get('doctrine')->getManager());
        }
        return $this->em;
    }

    /**
     * @param null $newSessionId
     */
    public function setNewSessionId($newSessionId)
    {
        $this->newSessionId = $newSessionId;
    }



    /**
     * @return string
     */
    public function getSessionId()
    {
        if (empty($this->newSessionId)) {
            return $this->getContainer()->get('session')->getId();
        } else {
            return $this->newSessionId;
        }
    }

    public function migrateCartBetweenSessionIds($oldSid, $newSid)
    {
        $carts = $this->getEm()->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'session' => $oldSid
            )
        );
        $cartsOptions = $this->getEm()->getRepository('FoodCartBundle:CartOption')->findBy(
            array(
                'session' => $oldSid
            )
        );
        foreach ($carts as $cart) {
            $cart->setSession($newSid);
        }
        foreach ($cartsOptions as $cartOp) {
            $cartOp->setSession($newSid);
        }
        $this->getEm()->flush();
    }

    /**
     * @param $dish
     * @param $option
     * @return $this
     */
    public function removeOption($dish, $option)
    {
        $this->getEm()->remove(
            $this->getEm()->getRepository('FoodCartBundle:CartOption')
                ->findOneBy(
                    array(
                        'dish_id' => $dish->getId(),
                        'dish_option_id' => $option->getId(),
                        'session' => $this->getSessionId()
                    )
                )
        );
        $this->getEm()->flush();
        return $this;
    }

    /**
     * @param int $dishId
     * @param int $cartId
     * @param int $placeId
     * @internal param $dish
     * @return CartService
     */
    public function removeDishByIds($dishId, $cartId, $placeId)
    {
        if (empty($placeId)) {
            $this->getContainer()->get('logger')->error('removeDishByIds called without place given. DishId: '.$dishId.' cartId: '.$cartId);
            return $this;
        }
        $dish = $this->getEm()->getRepository('FoodDishesBundle:Dish')->find((int)$dishId);
        $place = $this->getEm()->getRepository('FoodDishesBundle:Place')->find((int)$placeId);
        $opts = $this->getEm()->getRepository('FoodCartBundle:CartOption')
            ->findBy(
                array(
                    'dish_id' => $dish,
                    'cart_id' => (int)$cartId,
                    'session' => $this->getSessionId()
                )
            );
        foreach ($opts as $opt) {
            if ($opt == null) {
                $context = array(
                    'RequestData: '.json_encode($this->container->get('request')->request->all()),
                    'EntityType: '.get_class($opt),
                    'ParentType: '.get_class($dish)
                );
                $this->container->get('logger')->error(
                    "ACTION: removeDishByIds, options removal",
                    $context
                );
            } else {
                $this->getEm()->remove($opt);
                $this->getEm()->flush();
            }
        }

        $cartDish = $this->getEm()->getRepository('FoodCartBundle:Cart')
            ->findOneBy(
                array(
                    'dish_id' => $dish,
                    'cart_id' => (int)$cartId,
                    'place_id' => $place,
                    'session' => $this->getSessionId()
                )
            );

        if ($cartDish == null) {
            $context = array(
                'RequestData: '.json_encode($this->container->get('request')->request->all()),
                'EntityType: '.get_class($cartDish),
            );
            $this->container->get('logger')->error(
                "ACTION: removeDishByIds, Dish removal removal",
                $context
            );
        } else {
            $this->getEm()->remove($cartDish);
            $this->getEm()->flush();
        }

        return $this;
    }

    /**
     * @param $dish
     * @param $quantity
     * @return $this
     */
    public function setDishQuantity($dish, $quantity)
    {
        $dish = $this->getEm()->getRepository('FoodCartBundle:Cart')->findOneBy(
            array(
                'dish_id' => $dish->getId(),
                'session' => $this->getSessionId()
            )
        );
        $dish->setQuantity($quantity);
        $this->getEm()->flush();
        return $this;
    }

    /**
     * @param $dish
     * @param $option
     * @param $quantity
     * @return $this
     */
    public function setOptionQuantity($dish, $option, $quantity)
    {
        $dishOption = $this->getEm()->getRepository('FoodCartBundle:CartOption')->findOneBy(
            array(
                'dish_id' => $dish->getId(),
                'dish_option_id' => $option->getId(),
                'session' => $this->getSessionId()
            )
        );
        $dishOption->setQuantity($quantity);
        $this->getEm()->flush();
        return $this;
    }

    /**
     * @param $dishId
     * @param $optionId
     */
    public function removeOptionById($dishId, $optionId)
    {
        $dish = $this->getEm()->getRepository('FoodDishesBundle:Dish')->find($dishId);
        $option = $this->getEm()->getRepository('FoodDishesBundle:DishOption')->find($optionId);
        $this->removeOption($dish, $option);
    }

    /**
     * @param int $size
     * @param int $quantity
     * @param array $options
     * @param array $option
     */
    public function addDishBySizeId($size, $quantity, $options = array(), $option = array())
    {
        if (!is_array($options)) {
            $options = array();
        }
        $sizeEnt = $this->getEm()->getRepository('FoodDishesBundle:DishSize')->find($size);

        if(!empty($option)) {
            if (is_array($option)) {
                $options = array_merge($options, array_values($option));
            } else {
                $options[] = $option;
            }
        }
        $this->addDishByIds(
            $sizeEnt->getDish()->getId(),
            $size,
            $quantity,
            $options
        );
    }

    /**
     * @param $dish
     * @param $size
     * @param $quantity
     * @param $options
     */
    public function addDishByIds($dish, $size, $quantity, $options = array())
    {
        $dish = $this->getEm()->getRepository('FoodDishesBundle:Dish')->find($dish);
        $size = $this->getEm()->getRepository('FoodDishesBundle:DishSize')->find($size);
        $optionsEnt = array();
        if (!empty($options)) {
            foreach ($options as $optId) {
                $ent = $this->getEm()->getRepository('FoodDishesBundle:DishOption')->findOneBy(
                    array(
                        'id' => $optId,
                        'place' => $dish->getPlace()->getId()
                    )
                );
                if ($ent) {
                    $optionsEnt[] = $ent;
                }
            }
        }
        $this->addDish($dish, $size, $quantity, $optionsEnt);
    }

    /**
     * @param Dish $dish
     * @param DishSize $dishSize
     * @param $quantity
     * @param DishOption[] $options
     * @return $this
     */
    public function addDish(Dish $dish, DishSize $dishSize, $quantity, $options = array(), $comment = "", $sessionId = null)
    {
        $maxQuery = $this->getEm()->createQuery('SELECT MAX(c.cart_id) as top FROM FoodCartBundle:Cart c WHERE c.session = :session AND c.place_id= :place');
        $maxQuery->setParameters(
            array(
                'session' => $this->getSessionId(),
                'place' => $dish->getPlace()
            )
        );
        $itemId = $maxQuery->getSingleScalarResult();
        if (empty($itemId)) {
            $itemId = 1;
        } else {
            $itemId++;
        }

        $cartItem = new Cart();
        $cartItem->setPlaceId($dish->getPlace());
        $cartItem->setDishId($dish);
        $cartItem->setCartId($itemId);
        $cartItem->setSession(($sessionId != null ? $sessionId : $this->getSessionId()));
        $cartItem->setQuantity($quantity);
        $cartItem->setDishSizeId($dishSize);
        $cartItem->setComment($comment);
        $this->getEm()->persist($cartItem);
        $this->getEm()->flush();

        if (!empty($options)) {
            foreach ($options as $opt) {
                $cartOptionItem = new CartOption();
                $cartOptionItem->setSession(($sessionId != null ? $sessionId : $this->getSessionId()));
                $cartOptionItem->setDishId($dish);
                $cartOptionItem->setCartId($itemId);
                $cartOptionItem->setDishOptionId($opt);
                $this->getEm()->persist($cartOptionItem);
                $this->getEm()->flush();
            }
        }

       return $this;
    }


    /**
     * @param Place $place
     * @return array|\Food\CartBundle\Entity\Cart[]
     */
    public function getCartDishes($place)
    {
        $list = $this->getEm()->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'session' => $this->getSessionId(),
                'place_id' => $place->getId()
            )
        );
        foreach($list as $k => &$item) {
            $item->setEm($this->getEm());
        }
        return $list;
    }

    /**
     * Gauti patiekalo info carto dishams.
     *
     * @param int $dishId
     * @param int $cartId
     *
     * @return \Food\CartBundle\Entity\Cart
     */
    public function getCartDish($dishId, $cartId)
    {
        $cartEnt = $this->getEm()->getRepository('FoodCartBundle:Cart')->findOneBy(
            array(
                'session' => $this->getSessionId(),
                'dish_id' => $dishId,
                'cart_id' => $cartId
            )
        );

        // Set Object Manager only if cart found
        if ($cartEnt) {
            $cartEnt->setEm($this->getEm());
        }
        return $cartEnt;
    }

    /**
     * @param \Food\CartBundle\Entity\Cart[] $cartItems
     * @return float|int
     *
     * TODO Pauliau, ar cia dar reikalingas place'as?
     */
    public function getCartTotal($cartItems/*, $place*/)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += ((float)$cartItem->getDishSizeId()->getCurrentPrice() * 100) * (int)$cartItem->getQuantity();
            foreach ($cartItem->getOptions() as $opt) {
                $total += ((float)$opt->getDishOptionId()->getPrice() * 100) * (int)$cartItem->getQuantity();
            }
        }
        return $total / 100;
    }

    /**
     * @param \Food\CartBundle\Entity\Cart[] $cartItems
     * @return float|int
     */
    public function getCartTotalOld($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += $cartItem->getDishSizeId()->getPrice() * $cartItem->getQuantity();
            foreach ($cartItem->getOptions() as $opt) {
                $total += $opt->getDishOptionId()->getPrice() * $cartItem->getQuantity();
            }
        }
        return $total;
    }

    /**
     * @param Cart $cartItem
     * @return array|\Food\CartBundle\Entity\CartOption[]
     */
    public function getCartDishOptions(Cart $cartItem)
    {
        $list = $this->getEm()->getRepository('FoodCartBundle:CartOption')->findBy(
            array(
                'session' => $this->getSessionId(),
                'cart_id' => $cartItem->getCartId(),
                'dish_id' => $cartItem->getDishId()->getId()
            )
        );
        return $list;
    }

    /**
     * @param \Place $place
     * @return array
     */
    public function getCartDishesForJson($place)
    {
        $cartItems = $this->getCartDishes($place);
        $returnData = array();
        foreach ($cartItems as $cartItem) {
            $tmpRow = array(
                'name' => $cartItem->getDishId()->getName(),
                'price' => $cartItem->getDishSizeId()->getCurrentPrice(),
                'size' => $cartItem->getDishSizeId()->getUnit()->getName(),
                'quantity' => $cartItem->getQuantity(),
                'options' => $this->getOptionsForJson($cartItem)
            );
            $returnData[] = $tmpRow;
        }
        return $returnData;
    }

    /**
     * @param Cart $cartItem
     * @return array
     */
    private function getOptionsForJson(Cart $cartItem)
    {
        $returnData = array();
        $options = $this->getCartDishOptions($cartItem);
        foreach ($options as $cartOption) {
            $returnData[] = array(
                'name'      => $cartOption->getDishOptionId()->getName(),
                'price'     => $cartOption->getDishOptionId()->getPrice(),
                'quantity'  => $cartOption->getQuantity()
            );
        }
        return $returnData;
    }

    /**
     * @param Place $place
     */
    public function clearCart(Place $place)
    {
        $cartDishes = $this->getEm()->getRepository('FoodCartBundle:Cart')
            ->findBy(
                array(
                    'place_id' => $place,
                    'session' => $this->getSessionId()
                )
            );
        foreach ($cartDishes as $ck=>$cartDish) {
            $cartOptions = $this->getEm()->getRepository('FoodCartBundle:CartOption')
                ->findBy(
                    array(
                        'session' => $this->getSessionId(),
                        'dish_id' => $cartDish->getDishId(),
                        'cart_id' => $cartDish->getCartId()
                    )
                );
            foreach ($cartOptions as $co=>$cartOption) {
                $this->getEm()->remove($cartOption);
                $this->getEm()->flush();
            }
            $this->getEm()->remove($cartDish);
            $this->getEm()->flush();
        }
    }
}