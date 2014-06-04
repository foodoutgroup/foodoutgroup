<?php

namespace Food\ApiBundle\Common;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class Restaurant extends ContainerAware
{
    private $block = array(
        'restaurant_id' => null,
        'title' => '',
        'description' => '',
        'cuisine' => array(),
        'tags' => array(),
        'thumbnail_url' => '',
        'photos_urls' => array(),
        'menu_photos_enabled' => true,
        'payment_options' => array(),
        'services' => array(),
        'delivery_options' => array(
            'estimated_time' => 0,
            'price' => array(
                'amount' => 0,
                'currency' => 'LTL'
            ),
            'minimal_order' => array(
                'amount' => 0,
                'currency' => 'LTL'
            ),
        ),
        'is_working' => false,
        'is_taking_orders' => false,
        'order_hours' => array('','','','','','',''),
        'work_hours' => array('','','','','','',''),
        'locations' => array(),
    );

    public  $data;
    private $availableFields = array();

    public function __construct(Place $place = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($place);
        }
    }

    public function get($param) {
        $this->checkParam($param);
        return $this->data[$param];
    }

    /**
     * @param $param
     * @param $data
     * @return Restaurant $this
     */
    public function set($param, $data)
    {
        $this->checkParam($param);
        $this->data[$param] = array_merge($this->data[$param], $data);
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

    public function loadFromEntity(Place $place, PlacePoint $placePoint = null)
    {
        $kitchens = $place->getKitchens();
        $kitchensForResp = array();
        foreach ($kitchens as $kit) {
            $kitchensForResp[] = array(
                'id' => $kit->getId(),
                'name' => $kit->getName()
            );
        }


        $this
            ->set('restaurant_id', $place->getId())
            ->set('title', $place->getName())
            ->set('description', $place->getDescription())
            ->set('cuisine', $kitchensForResp)
            //->set('tags', array()) // @todo FILL IT !!
            ->set('thumbnail_url', 'http://www.foodout.lt/uploads/places/thumb_'.$place->getLogo())
            ->set(
                'payment_options',
                array(
                    'cash' => true, // @todo
                    'credit_card' => true // @todo
                )
            )
            ->set(
                'services',
                array(
                    'pickup' => true,
                    'delivery' => true // @todo - bus priklausomybe nuo PlacePoint. Taigi gal net ne cia turetu buti.
                )
            )
            ->set(
                'delivery_options',
                array(
                    'estimated_time' => $place->getDeliveryTime(),
                    'price' => array(
                        'amount' => $place->getDeliveryPrice() * 100,
                        'currency' => 'LTL'
                    ),
                    'minimal_order' => array(
                        'amount' => $place->getCartMinimum() * 100,
                        'currency' => 'LTL'
                    )
                )
            )
            ->set('is_working', !$this->container->get('food.order')->isTodayNoOneWantsToWork($place))
            ->set('is_taking_orders', !$this->container->get('food.order')->isTodayNoOneWantsToWork($place))
            ->set('locations', $this->_getLocationsForResponse($place, $placePoint));
    }

    private function _getLocationsForResponse(Place $place, PlacePoint $placePoint = null)
    {
        $points = $place->getPoints();
        $retData = array();
        foreach ($points as $point) {
            if ($point->getActive()) {
                $retData[] = array(
                    'location_id' => $point->getId(),
                    'address' => $point->getAddress(),
                    'city' => $point->getCity(),
                    'selected' => (!empty($placePoint) && $point->getId() == $placePoint->getId() ? true: false),
                    'coords' => array(
                        'latitude' => $point->getLat(),
                        'longitude' => $point->getLon()
                    ),
                    'is_working' => $this->container->get('food.order')->isTodayWork($point),
                    'work_hours' => array(              // @todo Fix kad jei nedirba nebutu tokia narkata.
                        $point->getWd1Start()." - ".$point->getWd1End(),
                        $point->getWd2Start()." - ".$point->getWd2End(),
                        $point->getWd3Start()." - ".$point->getWd3End(),
                        $point->getWd4Start()." - ".$point->getWd4End(),
                        $point->getWd5Start()." - ".$point->getWd5End(),
                        $point->getWd6Start()." - ".$point->getWd6End(),
                        $point->getWd7Start()." - ".$point->getWd7End()
                    ),
                    'phone_number' => $point->getPhone(),
                    'services' => array(
                        'pickup' => $point->getPickUp(),
                        'delivery' => $point->getDelivery()
                    )
                )
            }
        }
    }
}