<?php

namespace Food\ApiBundle\Common;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAware;

class Restaurant extends ContainerAware
{
    private $block = [
        'restaurant_id'       => null,
        'title'               => '',
        'description'         => '',
        'top'                 => false,
        'cuisine'             => [],
        'tags'                => [],
        'thumbnail_url'       => '',
        'photo_urls'          => [],
        'menu_photos_enabled' => true,
        'payment_options'     => [
            // 'cash' => true,
            // 'credit_card' => true
        ],
        'services'            => [
            // 'delivery'=>true,
            // 'pickup' => true
        ],
        'delivery_options'    => [
            'estimated_time' => 0,
            'price'          => [
                'amount'   => 0,
                'currency' => 'EUR'
            ],
            'minimal_order'  => [
                'amount'   => 0,
                'currency' => 'EUR'
            ],
        ],
        'is_working'          => false,
        'is_taking_orders'    => false,
        'order_hours'         => [],
        'work_hours'          => [],
        'locations'           => [],
    ];

    public $data;
    private $availableFields = [];

    /**
     * @param Place|null                                                $place
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(Place $place = null, $container = null)
    {
        $this->data = $this->block;
        $this->availableFields = array_keys($this->block);
        if (!empty($place)) {
            $this->loadFromEntity($place);
        }
        $this->container = $container;
    }

    /**
     * @param string $param
     *
     * @return mixed
     */
    public function get($param)
    {
        $this->checkParam($param);

        return $this->data[$param];
    }

    /**
     * @param string $param
     * @param mixed  $data
     *
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
     *
     * @throws \Exception
     */
    private function checkParam($param)
    {
        if (!in_array($param, $this->availableFields)) {
            throw new \Exception("Param: " . $param . ", was not found in fields list :)");
        }
    }

    /**
     * @param Place       $place
     * @param PlacePoint  $placePoint
     * @param bool        $pickUpOnly
     * @param array|null  $locationData
     * @param string|null $deliveryType
     *
     * @return $this
     */
    public function loadFromEntity(Place $place, PlacePoint $placePoint = null, $pickUpOnly = false, $locationData = null, $deliveryType = null)
    {
        $placeService = $this->container->get('food.places');
        if (empty($placePoint)) {
            foreach ($place->getPoints() as $pp) {
                if ($pp->getActive()) {
                    $placePoint = $pp;
                    break;
                }
            }
        }
        $kitchens = $place->getKitchens();
        $kitchensForResp = [];
        foreach ($kitchens as $kit) {
            $kitchensForResp[] = [
                'id'   => $kit->getId(),
                'name' => $kit->getName()
            ];
        }

        $photos = [];
        foreach ($place->getPhotos() as $photo) {
            if ($photo->getActive()) {
                $photos[] = '/uploads/covers/' . $photo->getPhoto();
            }
        }

        if ($pickUpOnly || $place->getDeliveryOptions() == $place::OPT_ONLY_PICKUP) {
            $pickUp = true;
            $delivery = false;
        } elseif ($locationData == null) {
            $delivery = false;
            $pickUp = false;
            foreach ($place->getPoints() as $tempPP) {
                if (!$tempPP->getActive()) {
                    continue;
                }
                if ($tempPP->getPickUp()) {
                    $pickUp = true;
                }
                if ($tempPP->getDelivery()) {
                    $delivery = true;
                }
                if ($pickUp && $delivery) {
                    break;
                }
            }
        } else {
            $pickUp = (isset($placePoint) && $placePoint->getPickUp() ? true : false);
            $delivery = (isset($placePoint) && $placePoint->getDelivery() ? true : false);
        }

        $devPrice = 0;
        $devCart = 0;
        if (!empty($locationData)) {
            $placePointMap = $this->container->get('session')->get('point_data');
            if (empty($placePointMap[$place->getId()])) {
                $ppId = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:Place')->getPlacePointNearWithDistance(
                    $place->getId(),
                    $locationData,
                    false,
                    true
                )
                ;

                $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($ppId);
            } else {
                // TODO Trying to catch fatal when searching for PlacePoint
                if (!isset($placePointMap[$place->getId()]) || empty($placePointMap[$place->getId()])) {
                    $this->container->get('logger')->error('Trying to find PlacePoint without ID in Restaurant - loadFromEntity find 2');
                }
                $pointRecord = $this->container->get('doctrine')->getManager()->getRepository('FoodDishesBundle:PlacePoint')->find($placePointMap[$place->getId()]);
            }

            $devPrice = $this->container->get('food.cart')->getDeliveryPrice(
                $place,
                $locationData,
                $pointRecord
            )
            ;
            $devCart = $this->container->get('food.cart')->getMinimumCart(
                $place,
                $locationData,
                $pointRecord
            )
            ;
        }

        $currency = $this->container->getParameter('currency_iso');
        $restaurantTitle = $place->getName();
        $restaurantTitle = str_replace(['„', '“'], '"', $restaurantTitle);

        $restaurantDesc = $place->getDescription();
        $restaurantDesc = str_replace(['„', '“'], '"', $restaurantDesc);

        $this
            ->set('restaurant_id', $place->getId())
            ->set('title', $restaurantTitle)
            ->set('description', $restaurantDesc)
            ->set('top', ($place->getTop() ? true : false))
            ->set('cuisine', $kitchensForResp)
            ->set('tags', [])// @todo FILL IT !!
            ->set('photo_urls', $photos)
            ->set('thumbnail_url', $place->getWebPath())
            ->set(
                'payment_options',
                [
                    'cash'        => ($place->getDisabledPaymentOnDelivery() ? false : true),
                    'credit_card' => ($place->getCardOnDelivery() && !$place->getDisabledPaymentOnDelivery() ? true : false)
                ]
            )
            ->set(
                'services',
                [
                    'pickup'   => $pickUp,
                    'delivery' => $delivery,
                ]
            )
            ->set(
                'delivery_options',
                [
                    'estimated_time'       => ((!empty($deliveryType) && $deliveryType == 'pickup') ? $place->getPickupTime() : $this->container->get('food.places')->getDeliveryTime($place)),
                    'price'                => [
                        'amount'   => (!empty($devPrice) ? ($devPrice * 100) : ($place->getDeliveryPrice() * 100)),
                        'currency' => $currency
                    ],
                    'minimal_order'        => [
                        'amount'   => (!empty($devCart) ? ($devCart * 100) : ($this->container->get('food.places')->getMinCartPrice($place->getId()) * 100)),
                        'currency' => $currency
                    ],
                    'minimal_order_pickup' => [
                        'amount'   => ($place->getMinimalOnSelfDel() ? $this->container->get('food.places')->getMinCartPrice($place->getId()) * 100 : 0),
                        'currency' => $currency
                    ]
                ]
            )
            ->set('is_working', !$this->container->get('food.order')->isTodayNoOneWantsToWork($place))
            ->set('is_taking_orders', !$this->container->get('food.order')->isTodayNoOneWantsToWork($place))
            ->set('order_hours', (isset($placePoint) ? $this->_getWorkHoursOfPlacePoint($placePoint) : null))
            ->set('work_hours', (isset($placePoint) ? $this->_getWorkHoursOfPlacePoint($placePoint) : null))
            ->set('locations', $this->_getLocationsForResponse($place, $placePoint))
        ;

        return $this;
    }

    /**
     * @param PlacePoint $point
     *
     * @return array
     */
    private function _getWorkHoursOfPlacePoint(PlacePoint $point)
    {
        // @TODO: make it possible for splitted time interval
        return [
            explode('-', $point->getWd1()),
            explode('-', $point->getWd2()),
            explode('-', $point->getWd3()),
            explode('-', $point->getWd4()),
            explode('-', $point->getWd5()),
            explode('-', $point->getWd6()),
            explode('-', $point->getWd7())
        ];
    }

    /**
     * @param Place      $place
     * @param PlacePoint $placePoint
     *
     * @return array
     */
    private function _getLocationsForResponse(Place $place, PlacePoint $placePoint = null)
    {
        $points = $place->getPoints();
        $retData = [];
        foreach ($points as $point) {
            if ($point->getActive()) {
                $retData[] = [
                    'location_id'  => $point->getId(),
                    'address'      => $point->getAddress(),
                    'city'         => $point->getCity(),
                    'selected'     => (!empty($placePoint) && $point->getId() == $placePoint->getId() ? true : false),
                    'coords'       => [
                        'latitude'  => $point->getLat(),
                        'longitude' => $point->getLon()
                    ],
                    'is_working'   => $this->container->get('doctrine')->getRepository('FoodDishesBundle:Place')->isPlacePointWorks($point),
                    'work_hours'   => $this->_getWorkHoursOfPlacePoint($point),
                    'phone_number' => $point->getPhone(),
                    /*
                    'services' => array(
                        'pickup' => $point->getPickUp(),
                        'delivery' => $point->getDelivery()
                    )
                    */
                ];
            }
        }

        return $retData;
    }
}
