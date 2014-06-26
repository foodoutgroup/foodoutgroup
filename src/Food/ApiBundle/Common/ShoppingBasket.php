<?php

namespace Food\ApiBundle\Common;

use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;

class ShoppingBasket extends ContainerAware{
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
            'currency' => 'LTL'
        ),
        'items' => array(),
    );
    private $data = null;

    private $availableFields = array();

    public function __construct(Cart $cart = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($cart);
        }
        $this->container = $container;
    }

    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param $param
     * @param $data
     * @return \MenuItem $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = $data;
        return $this;
    }

    /**
     * @param $param
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new Exception("Param: ".$param.", was not found in fields list :)");
        }
    }

    public function loadFromEntity(Cart $cart)
    {

    }
}