<?php
namespace Food\ApiBundle\Common;

use Food\CartBundle\Entity\Cart;
use Symfony\Component\DependencyInjection\ContainerAware;

class ShoppingBasketItem extends ContainerAware
{
    /**
     * @var array
     */
    private $block = array(
        "basket_item_id" => null,
        "item_id" => null,
        "size_id" => null,
        "count" => null,
        "options"=> array(
            //"option_id": 1,
            //"value": 2
        ),
        "additional_info" => "",
        "price"=> array(
            "amount" => 0,
            "amount_old" => 0,
            "currency"=> "EUR"
        )
    );

    /**
     * @var array
     */
    private $data = array();

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
     * @param string $param
     * @return mixed
     * @throws \Exception
     */
    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param string $param
     * @param mixed $data
     * @return \MenuItem $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = $data;
        return $this;
    }

    /**
     * @param string $param
     * @throws \Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new \Exception("Param: ".$param.", was not found in fields list :)");
        }
    }

    /**
     * @param array $requestPart
     * @return array
     */
    public function populateFromCreateRequest($requestPart)
    {
        $this->set('item_id', $requestPart['item_id'])
            ->set('count', $requestPart['count'])
            ->set('additional_info', $requestPart['additional_info'])
            ->set('options', $requestPart['options']);

        return $this->data;
    }

    /**
     * @param Cart $cartItem
     * @return array
     */
    public function loadFromEntity(Cart $cartItem)
    {
        $amount = $this->_contDaPriceOfAll($cartItem) * 100;
        $amountOld = ($cartItem->getDishId()->getShowDiscount() && $cartItem->getDishSizeId()->getDiscountPrice() > 0 ? $this->_contDaPriceOfAllOld($cartItem) * 100 : 0);
        if ($cartItem->getDishId()->getShowPublicPrice()) {
            $amountOld = 0;
        }
        $pricePart = array(
            'amount' => $amount,
            'amount_old' => $amountOld,
            'currency' => $this->container->getParameter('currency_iso')
        );
        if ($cartItem->getIsFree()) {
            $pricePart = array(
                'amount' => 0,
                'amount_old' => $amount,
                'currency' => $this->container->getParameter('currency_iso')
            );
        }
        $this->set('basket_item_id', $cartItem->getCartId()) // @todo - ar tikrai ?? :)
            ->set('item_id', $cartItem->getDishId()->getId())
            ->set('count', $cartItem->getQuantity())
            ->set('size_id', $cartItem->getDishSizeId()->getId())
            ->set('options', $this->_getOptions($cartItem))
            ->set('additional_info', ($cartItem->getComment() == null ? "" : $cartItem->getComment()))
            ->set(
                'price',
                $pricePart
            );
        return $this->data;
    }

    /**
     * @param Cart $cartItem
     * @return array
     */
    private function _getOptions(Cart $cartItem)
    {
        $returner = array();
        $cartItem->setEm($this->container->get('doctrine')->getManager());
        foreach($cartItem->getOptions() as $opt) {
            $returner[] = array(
                'option_id' => $opt->getDishOptionId()->getId()
            );
        }
        return $returner;
    }

    /**
     * @param Cart $cartItem
     * @return float|int
     */
    private function _contDaPriceOfAll(Cart $cartItem)
    {
        $cartItem->setEm($this->container->get('doctrine'));
        return $this->container->get('food.cart')->getCartTotalApi(array($cartItem), $cartItem->getPlaceId());
    }

    /**
     * @param Cart $cartItem
     * @return float|int
     */
    private function _contDaPriceOfAllOld(Cart $cartItem)
    {
        $cartItem->setEm($this->container->get('doctrine'));
        return $this->container->get('food.cart')->getCartTotalOld(array($cartItem), $cartItem->getPlaceId());
    }
}