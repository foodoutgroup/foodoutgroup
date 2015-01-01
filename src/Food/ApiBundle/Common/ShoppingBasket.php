<?php

namespace Food\ApiBundle\Common;

use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;

class ShoppingBasket extends ContainerAware{
    /**
     * @var array
     */
    private $block = array(
        'basket_id' => null,
        'restaurant_id' => null,
        'expires' => null, // unixtimestamp
        'payment_options' => array(
            'cash' => false,
            'credit_card' => false
        ),
        'total_price' => array(
            'amount' => 0,
            'discount' => 0,
            'currency' => 'EUR'
        ),
        'items' => array(),
    );

    /**
     * @var null
     */
    private $data = null;

    /**
     * @var array
     */
    private $availableFields = array();

    /**
     * @param Cart $cart
     * @param \Symfony\Component\DependencyInjection\ContainerInterface|null $container
     */
    public function __construct(Cart $cart = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($cart);
        }
        $this->container = $container;
    }

    /**
     * @param $param
     * @return mixed
     * @throws \Exception
     */
    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param $param
     * @param $data
     * @return MenuItem $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = $data;
        return $this;
    }

    /**
     * @param $param
     * @throws \Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new \Exception("Param: ".$param.", was not found in fields list :)");
        }
    }

    /**
     * @param Cart $cart
     */
    public function loadFromEntity(Cart $cart)
    {

    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }
}