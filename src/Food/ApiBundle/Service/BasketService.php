<?php
namespace Food\ApiBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;

class BasketService extends ContainerAware
{
    public function createBasketFromRequest(Request $request)
    {
        $data = array();
        return $this->_createBasket($data);
    }

    private function _createBasket($data)
    {
        return $this;
    }

    public function getBasket($id)
    {

    }
}