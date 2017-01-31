<?php

namespace Food\OrderBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Service\BaseService;
use Food\AppBundle\Utils\Misc;
use Food\CartBundle\Service\CartService;
use Food\DishesBundle\Entity\Place;

class OrderPriceService extends BaseService
{

    protected $cartService;
    protected $miscService;

    public function __construct(EntityManager $em, CartService $cartService, Misc $miscService)
    {
        parent::__construct($em);

        $this->cartService = $cartService;
        $this->miscService = $miscService;
    }

    /**
     * @return array
     */
    public function getOrderPrices(Place $place)
    {
        $prices = [];

        return $prices;
    }
}
