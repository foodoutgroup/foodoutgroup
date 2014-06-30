<?php
namespace Food\ApiBundle\Common;

use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;

class ShoppingBasketItem extends ContainerAware
{
    private $block = array(
        "basket_item_id" => null,
        "item_id" => null,
        "count" => null,
        "options"=> array(
            //"option_id": 1,
            //"value": 2
        ),
        "additional_info" => "",
        "price"=> array(
            "amount" => 0,
            "currency"=> "LTL"
        )
    );

    private $data = array();
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


    public function loadFromEntity(Cart $cartItem)
    {
        $this->set('basket_item_id', $cartItem->getCartId()) // @todo - ar tikrai ?? :)
            ->set('item_id', $cartItem->getDishId())
            ->set('count', $cartItem->getQuantity())
            ->set('options', array())
            ->set('additional_info', $cartItem->getComment())
            ->set(
                'price',
                array(
                    'amount' => $this->_contDaPriceOfAll($cartItem) * 100,
                    'currency' => 'LTL'
                )
            )
        return $this->data;
    }

    private function _contDaPriceOfAll(Cart $cartItem)
    {
        $total = 0;
        return $total;
    }
}