<?php

namespace Food\CallCenterBundle\Controller\Decorators;

use Food\UserBundle\Entity\User;

trait DefaultDecorator
{
    protected function getLocationForm()
    {
        $choices = $this->getCityChoices();

        return $this->createFormBuilder()
                    ->add('city',
                          'choice',
                          ['choices' => $choices,
                           'attr' => ['class' => 'form-control',
                                       'tabindex' => 1]])
                    ->add('street',
                          'text',
                          ['attr' => ['class' => 'form-control',
                                      'tabindex' => 2]])
                    ->add('house',
                          'text',
                          ['attr' => ['class' => 'form-control',
                                      'tabindex' => 3]])
                    ->getForm();
    }

    protected function getPlaces($location, $filters)
    {
        $places = $this->getDoctrine()
                       ->getManager()
                       ->getRepository('FoodDishesBundle:Place')
                       ->magicFindByKitchensIds([], $filters, true, $location);
        $this->get('food.places')->saveRelationPlaceToPoint($places);
        $places = $this->get('food.places')->placesPlacePointsWorkInformation($places);

        if (empty($location) || $location['not_found']) {
            return $places;
        }

        $distanceScores = [];

        foreach ($places as $key => $place) {
            $point = $place['point'];
            $latDelta = abs($location['lat'] - $point->getLat());
            $lonDelta = abs($location['lng'] - $point->getLon());
            $distanceScores[$key] = ($latDelta + $lonDelta) / 2.0;
        }

        asort($distanceScores);

        $closestPlaces = [];

        foreach ($distanceScores as $key => $score) {
            $places[$key]['distance_score'] = $score;
            $closestPlaces[] = $places[$key];
        }

        return $closestPlaces;
    }

    protected function getUserPlace()
    {
        return \Maybe($this->getUser())->getPlace();
    }

    protected function getPlaceById($id)
    {
        return $this->getDoctrine()
                    ->getRepository('FoodDishesBundle:Place')
                    ->find($id);
    }

    protected function getPlacesForm(array $places)
    {
        $choices = $this->getChoicesFromPlaces($places);

        return $this->createFormBuilder()
                    ->add('place',
                          'hidden',
                          ['attr' => ['class' => 'select2',
                                      'id' => 'form_place']])
                    ->getForm();
    }

    protected function getChoicesFromPlaces(array $places)
    {
        $choices = [];

        foreach ($places as $place) {
            if (is_object($place)) {
                $choices[$place->getId()] = $place->getName();
            } else {
                $choices[$place['place_id']] = $place['place']->getName();
            }
        }

        return $choices;
    }

    protected function getDishesByPlace($place)
    {
        return $this->getDoctrine()
                    ->getRepository('FoodDishesBundle:Dish')
                    ->findBy(['place' => $place], ['name' => 'asc']);
    }

    protected function putPlaceIntoSession($placeId)
    {
        $this->get('session')->set(static::SESSION_CALLCENTER_PLACE, $placeId);
    }

    protected function getPlaceFromSession()
    {
        return $this->get('session')->get(static::SESSION_CALLCENTER_PLACE, 0);
    }

    protected function removePlaceFromSession()
    {
        if ($this->getPlaceFromSession()) {
            $this->get('session')->remove(static::SESSION_CALLCENTER_PLACE);
        }
    }

    protected function putUserIntoSession($userId)
    {
        $this->get('session')->set(static::SESSION_CALLCENTER_USER, $userId);

        return $this;
    }

    protected function getUserFromSession($entity = false)
    {
        $userId = $this->get('session')->get(static::SESSION_CALLCENTER_USER, 0);
        if ($entity) {
            return $this->get('doctrine')->getRepository('FoodUserBundle:User')->find($userId);
        }

        return $userId;
    }

    protected function removeUserFromSession()
    {
        $session = $this->get('session');
        if ($session->has(static::SESSION_CALLCENTER_USER)) {
            $session->remove(static::SESSION_CALLCENTER_USER);
        }

        return $this;
    }
    
    protected function putAddressIntoSession($addressId)
    {
        $this->get('session')->set(static::SESSION_CALLCENTER_ADDRESS, $addressId);

        return $this;
    }

    protected function getAddressFromSession($entity = false)
    {
        $addressId = $this->get('session')->get(static::SESSION_CALLCENTER_ADDRESS, 0);
        if ($entity) {
            return $this->get('doctrine')->getRepository('FoodUserBundle:UserAddress')->find($addressId);
        }

        return $addressId;
    }

    protected function removeAddressFromSession()
    {
        $session = $this->get('session');
        if ($session->has(static::SESSION_CALLCENTER_ADDRESS)) {
            $session->remove(static::SESSION_CALLCENTER_ADDRESS);
        }

        return $this;
    }

    protected function reset()
    {
        $place = $this->getPlaceById($this->getPlaceFromSession());

        if ($place) {
            $this->get('food.cart')->clearCart($place);
            $this->removePlaceFromSession();
            // $this->get('food.googlegis')->removeLocationFromSession();
        }

        $this->removeUserFromSession();
        $this->removeAddressFromSession();
    }

    protected function getCityChoices()
    {
        return $this->cities;
    }
}
