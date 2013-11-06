<?php

namespace Food\CartBundle\Service;
use Food\DishesBundle\Entity\Dish;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
use Symfony\Component\DependencyInjection\Container;


class CartService {
    private $container;
    private $userId;

    public function __construct(Container $container, $userId)
    {
        $this->container = $container;
        $this->userId = $userId;
    }

    /**
     * @param Dish $dish
     * @param $quantity
     * @param array $options
     * @return $this
     *
     * @todo NOT FINAL - Just testing :)
     */
    public function addDish(Dish $dish, $quantity, $options = array()) {
        $cartItem = new Cart();
        $cartItem->setDishId($dish);
        $cartItem->setUser($this->userId);
        $cartItem->setQuantity($quantity);

        $em = $this->getDoctrine()->getManager();
        $em->persist($cartItem);
        $em->flush();


        if (empty($options)) {
            foreach ($options as $opt) {
                $cartOptionItem = new CartOption();
                $cartOptionItem->setUser($this->userId);
                $cartOptionItem->setDishId($dish);
                $cartOptionItem->setDishOptionId($opt['option']);
                $cartOptionItem->setQuantity($opt['quantity']);
                $em->persist($cartOptionItem);
                $em->flush();
            }
        }

       return $this;
    }
}