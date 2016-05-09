<?php

namespace Food\CallCenterBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Collections\ArrayCollection;
use Food\CallCenterBundle\Controller\Decorators\DefaultDecorator;

/**
 * @Route("/callcenter")
 */
class DefaultController extends Controller
{
    const SESSION_CALLCENTER_PLACE = 'callcenter_place';

    protected $cities = ['Vilnius' => 'Vilnius',
                         'Kaunas' => 'Kaunas',
                         'Klaipėda' => 'Klaipėda'];

    use DefaultDecorator;

    /**
     * @Route("/", name="food_callcenter")
     * @Template
     */
    public function indexAction()
    {
        $placeService = $this->get('food.places');
        $userPlace = $this->getUserPlace();
        $place = $userPlace->is_some() ? $userPlace->val() : $this->getPlaceById($this->getPlaceFromSession());
        $inCart = false;
        $hideDelivery = false;
        $applyDiscount = false;
        $discountSize = 0.0;
        $discountSum = 0.0;

        $locData = $this->get('food.googlegis')->getLocationFromSession();
        $places = $userPlace->is_none() ? $this->getPlaces($locData, []) : [$userPlace->val()];
        if (empty($place) && count($places) > 1) {
            if (is_array($places) && !is_object($places)) {
                $place = $places[0];
            } else {
                $place = $places->first();
            }
            $place = $place['place'];
        }

        $list = $this->get('food.cart')->getCartDishes($place);
        $totalCart = $this->get('food.cart')->getCartTotal($list);

        $places = new ArrayCollection($places);
        if (is_null($place) && count($places) > 1 && !is_null($locData)) {
            if (is_array($places) && !is_object($places)) {
                $place = $places[0];
            } else {
                $place = $places->first();
            }
            $place = $place['place'];
        }
        // location form
        $locationForm = $this->getLocationForm();
        if (!empty($locData) && empty($locData['not_found'])) {
            $locationForm->get('city')->setData($locData['city']);
            $locationForm->get('street')->setData($locData['street']);
            $locationForm->get('house')->setData($locData['street_nr']);
        }

        return [
            'location_form' => $locationForm->createView(),
            'places' => $places->toArray(),
            'place_form' => $this->getPlacesForm($places->toArray())->createView(),
            'place' => $place,
            'list' => $list,
            'inCart' => (int)$inCart,
            'hide_delivery' => $hideDelivery,
            'applyDiscount' => $applyDiscount,
            'discountSize' => $discountSize,
            'discountSum' => $discountSum,
            'total_cart' => $totalCart,
            'total_with_delivery' => $totalCart + \Maybe($place)->getDeliveryPrice()->val(0),
            'selected_place' => $this->getPlaceFromSession() ? true : false,
            'location' => $this->get('food.googlegis')->getLocationFromSession(),
            'isCallcenter' => true,
            'location_data' => $locData,
            'userAllAddress' => $placeService->getCurrentUserAddresses(),
        ];
    }

    /**
     * @Route("/load-menu/{placeId}", name="food_callcenter_load_menu", options={"expose"=true})
     * @Template
     */
    public function loadMenuAction($placeId)
    {
        $place = $this->getPlaceById($placeId);
        $dishes = $this->getDishesByPlace($place);

        // save selected place into session
        $this->putPlaceIntoSession($placeId);

        return [
            'items' => $dishes,
            'isCallcenter' => true
        ];
    }

    /**
     * @Route("/reset", name="food_callcenter_reset", options={"expose"=true})
     * @Template
     */
    public function resetAction()
    {
        $this->reset();

        return [];
    }

    /**
     * @Route("/checkout", name="food_callcenter_checkout", options={"expose"=true})
     * @Template("FoodCartBundle:Default:form.html.twig")
     */
    public function checkoutAction()
    {
        $placeId = $this->getPlaceFromSession();

        if (!$placeId) return new Response('');

        return [
            'place' => $this->getPlaceById($placeId),
            'formHasErrors' => false,
            'order' => null,
            'takeAway' => false,
            'location' => $this->get('food.googlegis')->getLocationFromSession(),
            'dataToLoad' => [],
            'isCallcenter' => true,
            'submitted' => false
        ];
    }

    /**
     * @Route("/retrieve-location", name="food_callcenter_retrieve_location", options={"expose"=true})
     * @Template
     */
    public function retrieveLocationAction()
    {
        $location = $this->get('food.googlegis')->getLocationFromSession();
        $data = ['city' => \Maybe($location['city'])->val(''),
                 'address_orig' => \Maybe($location['address_orig'])->val('')];

        return new Response(json_encode($data));
    }

    /**
     * @Route("/get-places-by-location", name="food_callcenter_get_places_by_location", options={"expose"=true})
     * @Template
     */
    public function getPlacesByLocationAction()
    {
        $result = [];
        $recommended = false;
        $userPlace = $this->getUserPlace();
        $place = $userPlace->is_some() ? $userPlace->val() : $this->getPlaceById($this->getPlaceFromSession());
        $filters = [];
        $placeId = 0;

        // get places from location
        if ($userPlace->is_some()) {
            $filters['id'] = $userPlace->getId()->val(0);
        }

        // get location data
        $locData = $this->get('food.googlegis')->getLocationFromSession();

        $places = $this->getPlaces($locData, $filters);
        $places = new ArrayCollection($places);

        // format places
        foreach ($places as $value) {
            $result[] = ['id' => $value['place_id'],
                         'text' => $value['place']->getName()];
        }

        if (is_null($place) && count($places) > 1 && !is_null($locData)) {
            $place = $places->first();
            $placeId = $place['place_id'];
        } else {
            $placeId = $place->getId();
        }

        return new JsonResponse(['places' => $result, 'location' => $locData, 'place' => $placeId]);
    }

    /**
     * @Route("/get-location", name="food_callcenter_get_location", options={"expose"=true})
     * @Template
     */
    public function getLocationAction()
    {
        $locData = $this->get('food.googlegis')->getLocationFromSession();

        return new JsonResponse($locData);
    }

    /**
     * @Route("/get-address-by-phone/{phone}", name="food_callcenter_get_address_by_phone", options={"expose"=true})
     * @Template
     */
    public function getAddressAction($phone)
    {
        $userByPhone = $this->container->get('doctrine')->getRepository('FoodUserBundle:User')->findOneBy(array('phone' => $phone));
        $addresses = array();

        if ($userByPhone) {
            $addressesEnts = $this->container->get('doctrine')->getRepository('FoodUserBundle:UserAddress')->findBy(array('user' => $userByPhone));
            foreach ($addressesEnts as $addrRow) {
                $addr = $addrRow->getAddress();
                $streetAddr = "";
                $houseNumber = "";
                if (preg_match("/(\d+\w*\s*-\s*\d+)/i", $addr, $matches)) {
                    $addressSplt = explode("-", $matches[1]);
                    $tmp = $addressSplt[0];
                    if ($tmp == intval($tmp)) {
                        $streetAddr = strstr($addr, $matches[1], true);
                        $houseNumber = $tmp;
                    }
                } else {
                    $addressSplt = explode(" ", $addr);
                    $houseNumber = end($addressSplt);
                    unset($addressSplt[sizeof($addressSplt)-1]);
                    $streetAddr = implode(" ", $addressSplt);
                }
                $addresses[] = array(
                    'city' => $addrRow->getCity(),
                    'address' => $addrRow->getAddress(),
                    'street' => $streetAddr,
                    'house' => $houseNumber
                );
            }
        }

        return array(
            'user_found' => ($userByPhone ? true: false),
            'user' => $userByPhone,
            'addresses' => $addresses
        );
    }
}
