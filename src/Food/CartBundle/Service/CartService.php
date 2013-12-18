<?php

namespace Food\CartBundle\Service;
use Doctrine\Tests\Common\DataFixtures\ReferenceRepositoryTest;
use Food\DishesBundle\Entity\Dish;
use Food\CartBundle\Entity\Cart;
use Food\CartBundle\Entity\CartOption;
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
     */
    public function getSessionId()
    {
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
    public function removeDish($dish)
    {
        $this->getEm()->remove(
            $this->getEm()->getRepository('FoodCartBundle:Cart')
                ->findOneBy(
                    array(
                        'dish_id' => $dish->getId(),
                        'session' => $this->getSessionId()
                    )
                )
        );
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
        /*
        $q = $this->getContainer()->get('doctrine')
            ->update('Account')
            ->set('amount', 'amount + 200')
            ->where('id > 200');
        */
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
     * @param Dish $dish
     * @param $quantity
     * @param array $options
     * @return $this
     *
     * Just test :)
     */
    public function addDish(Dish $dish, $quantity, $options = array()) {
        $cartItem = new Cart();
        $cartItem->setDishId($dish);
        $cartItem->setSession($this->getSessionId());
        $cartItem->setQuantity($quantity);

        $this->getEm()->persist($cartItem);
        $this->getEm()->flush();


        if (!empty($options)) {
            foreach ($options as $opt) {
                $cartOptionItem = new CartOption();
                $cartOptionItem->setSession($this->getSessionId());
                $cartOptionItem->setDishId($dish);
                $cartOptionItem->setDishOptionId($opt['option']);
                $cartOptionItem->setQuantity($opt['quantity']);
                $this->getEm()->persist($cartOptionItem);
                $this->getEm()->flush();
            }
        }

       return $this;
    }
}