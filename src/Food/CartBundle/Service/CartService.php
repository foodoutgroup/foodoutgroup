<?php

namespace Food\CartBundle\Service;
use Doctrine\Tests\Common\DataFixtures\ReferenceRepositoryTest;
use Food\DishesBundle\Entity\Dish;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
use Food\DishesBundle\Entity\DishOption;
use Food\DishesBundle\Entity\DishSize;
use Food\DishesBundle\Entity\Place;
use Symfony\Component\DependencyInjection\Container;


class CartService {

    private $container;
    /**
     * @var  \Doctrine\Common\Persistence\ObjectManager
     */
    private $em;

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
     * @return string
     *
     * @todo Panaikinti hardcoded dummy sesion id !!!!!
     */
    public function getSessionId()
    {
        return 123;
        return $this->getContainer()->get('session')->getId();
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
     * @param $dish
     * @return $this
     */
    public function removeDishByIds($dishId, $cartId, $placeId)
    {
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
            $this->getEm()->remove($opt);
            $this->getEm()->flush();
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

        $this->getEm()->remove($cartDish);
        $this->getEm()->flush();

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
     */
    public function addDishBySizeId($size, $quantity, $options = array(), $option = array())
    {
        $sizeEnt = $this->getEm()->getRepository('FoodDishesBundle:DishSize')->find($size);
        if(!empty($option)) {
            $options[] = $option;
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
    public function addDish(Dish $dish, DishSize $dishSize, $quantity, $options = array())
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
        $cartItem->setSession($this->getSessionId());
        $cartItem->setQuantity($quantity);
        $cartItem->setDishSizeId($dishSize);
        $this->getEm()->persist($cartItem);
        $this->getEm()->flush();

        if (!empty($options)) {
            foreach ($options as $opt) {
                $cartOptionItem = new CartOption();
                $cartOptionItem->setSession($this->getSessionId());
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
     * @param \Place $place
     * @return array|\Food\CartBundle\Entity\Cart[]
     */
    public function getCartDishes($place)
    {
        $list = $this->getEm()->getRepository('FoodCartBundle:Cart')->findBy(
            array(
                'session' => $this->getSessionId(),
                'place_id' => $place
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
        $cartEnt->setEm($this->getEm());
        return $cartEnt;
    }

    /**
     * @param \Food\CartBundle\Entity\Cart[] $cartItems
     * @param \Food\DishesBundle\Entity\Place $place
     */
    public function getCartTotal($cartItems, $place)
    {
        $total = 0;
        foreach ($cartItems as $cartItem) {
            $total += $cartItem->getDishSizeId()->getPrice() * $cartItem->getQuantity();
            foreach ($cartItem->getOptions() as $opt) {
                $total += $opt->getDishOptionId()->getPrice() * $cartItem->getQuantity();
            }
        }
        $total += $place->getDeliveryPrice();
        return $total;
    }

    /**
     * @param Dish $dish
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
                'price' => $cartItem->getDishSizeId()->getPrice(),
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