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
        'payment_options' => array(
            // 'cash' => true,
            // 'credit_card' => true
        ),
        'services' => array(
            // 'delivery'=>true,
            // 'pickup' => true
        ),
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
        'order_hours' => array(),
        'work_hours' => array(),
        'locations' => array(),
    );

    public  $data;
    private $availableFields = array();

    public function __construct(Place $place = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($place);
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
     * @return Restaurant $this
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
            ->set('tags', array()) // @todo FILL IT !!
            ->set('thumbnail_url', 'http://www.foodout.lt/uploads/places/thumb_'.$place->getLogo())
            ->set(
                'payment_options',
                array(
                    'cash' => true,
                    'credit_card' => ($place->getSelfDelivery() ? false: true)
                )
            )
            ->set(
                'services',
                array(
                    'pickup' => (isset($placePoint) && $placePoint->getPickUp() ? true: false),
                    'delivery' => (isset($placePoint) && $placePoint->getDelivery() ? true: false)
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
            ->set('order_hours', (isset($placePoint) ? $this->_getWorkHoursOfPlacePoint($placePoint) : null))
            ->set('work_hours', (isset($placePoint) ? $this->_getWorkHoursOfPlacePoint($placePoint) : null))
            ->set('locations', $this->_getLocationsForResponse($place, $placePoint));
        return $this;
    }

    private function _getWorkHoursOfPlacePoint(PlacePoint $point)
    {
        return array(
            (strpos($point->getWd1Start(), ":") > 0 ? array($point->getWd1Start(),$point->getWd1End()) : array()),
            (strpos($point->getWd2Start(), ":") > 0 ? array($point->getWd2Start(),$point->getWd2End()) : array()),
            (strpos($point->getWd3Start(), ":") > 0 ? array($point->getWd3Start(),$point->getWd3End()) : array()),
            (strpos($point->getWd4Start(), ":") > 0 ? array($point->getWd4Start(),$point->getWd4End()) : array()),
            (strpos($point->getWd5Start(), ":") > 0 ? array($point->getWd5Start(),$point->getWd5End()) : array()),
            (strpos($point->getWd6Start(), ":") > 0 ? array($point->getWd6Start(),$point->getWd6End()) : array()),
            (strpos($point->getWd7Start(), ":") > 0 ? array($point->getWd7Start(),$point->getWd7End()) : array())
        );
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
                    'work_hours' => $this->_getWorkHoursOfPlacePoint($point),
                    'phone_number' => $point->getPhone(),
                    /*
                    'services' => array(
                        'pickup' => $point->getPickUp(),
                        'delivery' => $point->getDelivery()
                    )
                    */
                );
            }
        }
    }
}