<?php

namespace Food\CartBundle\Service;

use Food\DishesBundle\Entity\ComboDiscount;
use Food\DishesBundle\Entity\Dish;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\DishSize;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;


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
        }

        return $this->newSessionId;
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
     * @param int $quantity
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
    public function addDish(Dish $dish, DishSize $dishSize, $quantity, $options = array(), $comment = "", $sessionId = null, $isFree = false)
    {
        if ($this->getContainer()->get('food.dishes')->isDishAvailable($dish)) {
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
            $cartItem->setIsFree($isFree);
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
            if (!$cartItem->getIsFree()) {
                $total += ((float)$cartItem->getDishSizeId()->getCurrentPrice() * 100) * (int)$cartItem->getQuantity();
                foreach ($cartItem->getOptions() as $opt) {
                    $total += ((float)$opt->getDishOptionId()->getPrice() * 100) * (int)$cartItem->getQuantity();
                }
            }
        }
        $total = sprintf("%01.2f", $total / 100);
        return $total;
    }

    /**
     * @param \Food\CartBundle\Entity\Cart[] $cartItems
     * @return float|int
     */
    public function getCartTotalApi($cartItems/*, $place*/)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            if (!$cartItem->getIsFree()) {
                $totalPart = ((float)$cartItem->getDishSizeId()->getCurrentPrice() * 100) * (int)$cartItem->getQuantity();
                if ($cartItem->getDishId()->getShowPublicPrice()) {
                    $totalPart = ((float)$cartItem->getDishSizeId()->getPublicPrice() * 100) * (int)$cartItem->getQuantity();
                }
                $total += $totalPart;
                foreach ($cartItem->getOptions() as $opt) {
                    $totalOpt = ((float)$opt->getDishOptionId()->getPrice() * 100) * (int)$cartItem->getQuantity();
                    if ($cartItem->getDishId()->getShowPublicPrice()) {
                        $totalOpt = 0;
                    }
                    $total += $totalOpt;
                }
            }
        }
        return $total / 100;
    }

    /**
     * Total nenuolaidiniu prekiu. Reikalinga esant poreikiui perskaiciuoti procentine nuolaida.
     *
     * @param Cart[] $cartItems
     * @return int
     *
     * @deprecated
     */
    public function getCartTotalOfNonDiscounted($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            if (!$cartItem->getIsFree()) {
                $thisDishFitsUs = false;
                if (!$cartItem->getPlaceId()->getDiscountPricesEnabled()) {
                    $thisDishFitsUs = true;
                } elseif (!$cartItem->getDishId()->getDiscountPricesEnabled()) {
                    $thisDishFitsUs = true;
                } elseif ($cartItem->getDishId()->getDiscountPricesEnabled() && $cartItem->getPlaceId()->getDiscountPricesEnabled() && $cartItem->getDishSizeId()->getDiscountPrice() == 0) {
                    $thisDishFitsUs = true;
                }
                if ($thisDishFitsUs) {
                    $total += $cartItem->getDishSizeId()->getCurrentPrice() * $cartItem->getQuantity();
                    foreach ($cartItem->getOptions() as $opt) {
                        $total += $opt->getDishOptionId()->getPrice() * $cartItem->getQuantity();
                    }
                }
            }
        }
        return $total;
    }

    /**
     * @param $cartItems
     * @param $discountPercent
     * @return float|int
     */
    public function getTotalDiscount($cartItems, $discountPercent)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $thisDishFitsUs = false;
            if (!$cartItem->getPlaceId()->getDiscountPricesEnabled()) {
                $thisDishFitsUs = true;
            } elseif (!$cartItem->getDishId()->getDiscountPricesEnabled()) {
                $thisDishFitsUs = true;
            } elseif ($cartItem->getDishId()->getDiscountPricesEnabled() && $cartItem->getPlaceId()->getDiscountPricesEnabled() && $cartItem->getDishSizeId()->getDiscountPrice() == 0) {
                $thisDishFitsUs = true;
            }
            if ($cartItem->getDishId()->getNoDiscounts()) {
                $thisDishFitsUs = false;
            }
            $theDish = 0;
            if ($thisDishFitsUs) {
                $theDish+= $cartItem->getDishSizeId()->getCurrentPrice() * $cartItem->getQuantity();
                foreach ($cartItem->getOptions() as $opt) {
                    $theDish += $opt->getDishOptionId()->getPrice() * $cartItem->getQuantity();
                }
                if ($theDish > 0) {
                    $total+= round(($theDish * $discountPercent / 100), 2);
                }
            }
        }
        return $total;
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

    /**
     * @param Place $place
     * @param $locData
     * @param PlacePoint $placePoint
     * @return int
     */
    public function getDeliveryPrice(Place $place, $locData, PlacePoint $placePoint)
    {
        $deliveryTotal = $this->container->get('doctrine')->getManager()->getRepository("FoodDishesBundle:Place")->getDeliveryPriceForPlacePoint($place, $placePoint, $locData);
        if (empty($deliveryTotal)) {
            $deliveryTotal = $place->getDeliveryPrice();
        }
        return $deliveryTotal;
    }

    /**
     * @param Place $place
     * @param $locData
     * @param PlacePoint $placePoint
     * @return float
     */
    public function getMinimumCart(Place $place, $locData, PlacePoint $placePoint)
    {
        $deliveryTotal = $this->container->get('doctrine')->getManager()->getRepository("FoodDishesBundle:Place")->getMinimumCartForPlacePoint($place, $placePoint, $locData);
        if (empty($deliveryTotal)) {
            $deliveryTotal = $place->getCartMinimum();
        }
        $deliveryTotal = sprintf("%01.2f", $deliveryTotal);
        return $deliveryTotal;
    }

    public function recalculateBundles($placeId)
    {
        $place = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:Place')->find($placeId);
        $cartItems = $this->getCartDishes($place);
        $dishUnits = array();
        $amounts = array();
        $activeBundles = $this->_getActiveBundles($place);

        foreach ($cartItems as $item) {
            $item->setIsFree(false);
            $this->getEm()->persist($item);
            $this->getEm()->flush();
            $amounts[$item->getCartId()] = $item->getQuantity();
            $dishCategories = $item->getDishId()->getCategories();

            foreach ($activeBundles as $comboDiscount) {
                if ($comboDiscount->getApplyBy() == ComboDiscount::OPT_COMBO_APPLY_UNIT) {
                    if ($comboDiscount->getDishUnit()->getId() == $item->getDishSizeId()->getUnit()->getId()) {
                        if (!empty($comboDiscount->getDishCategory())) {
                            foreach ($dishCategories as $dishCategory) {
                                if ($comboDiscount->getDishCategory()->getId() == $dishCategory->getId()) {
                                    $dishUnits[$item->getDishSizeId()->getUnit()->getId()][] = $item;
                                }
                            }
                        } else {
                            $dishUnits[$item->getDishSizeId()->getUnit()->getId()][] = $item;
                        }
                    }
                }
            }
        }

        $dishUnitsIds = array_keys($dishUnits);

        foreach ($activeBundles as $bund) {
            if ($bund->getApplyBy() == ComboDiscount::OPT_COMBO_APPLY_UNIT) {
                if (in_array($bund->getDishUnit()->getId(), $dishUnitsIds)) {
                    $this->_applyUnitBundles($bund, $dishUnits[$bund->getDishUnit()->getId()]);
                }
            }
            if ($bund->getApplyBy() == ComboDiscount::OPT_COMBO_APPLY_CATEGORY) {
                @mail("paulius@foodout.lt",  "OPT_COMBO_APPLY_CATEGORY not implemented", "OPT_COMBO_APPLY_CATEGORY not implemented", "FROM: info@foodout.lt");
            }
        }
    }

    /**
     * @param ComboDiscount $bundle
     * @param Cart[] $items
     */
    private function _applyUnitBundles($bundle, $items)
    {
        $amount = $bundle->getAmount();
        $totalDishes = 0;
        foreach ($items as $item) {
            $totalDishes+= $item->getQuantity();
        }
        $splits = floor($totalDishes /  ($amount+1)); // splitas kad gauti kiek paketu gaunasi taikant Bundla ze free (kiek reikia surinkti + tas kuris bus free)
        /**
         * kacialinam pigiausius dabar, nes butent juos discountinsim
         */
        $thePricesIdMap = [];
        $thePriceMapper = [];
        $theIdMap = [];
        foreach($items as $dish) {
            for($i = 0; $i < $dish->getQuantity(); $i++) {
                $thePricesIdMap[] = array('dish' => $dish, 'price' => $dish->getDishSizeId()->getCurrentPrice());
                $thePriceMapper[] = $dish->getDishSizeId()->getCurrentPrice();
            }
            $theIdMap[$dish->getCartId()] = $dish;

        }
        array_multisort($thePriceMapper, SORT_ASC, $thePricesIdMap);
        for ($i = 0; $i < $splits; $i++) {
            $item = $thePricesIdMap[$i]['dish'];
            $quan = $item->getQuantity();
            $item->setEm($this->getEm());
            if ($quan == 1) {
                $item->setIsFree(true);
                $this->getEm()->persist($item);
                $this->getEm()->flush();
            } else {
                $item->setQuantity($quan-1);
                $this->getEm()->persist($item);
                $this->getEm()->flush();
                $this->addDish(
                    $item->getDishId(),
                    $item->getDishSizeId(),
                    1,
                    $item->getOptions(),
                    "",
                    null,
                    true
                );
            }
        }

    }

    private function _getActiveBundles(Place $place)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('FoodDishesBundle:ComboDiscount')
            ->findBy(
                array(
                    'place' => $place,
                    'active' => 1
                )
            );
    }
}
