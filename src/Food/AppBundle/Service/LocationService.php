<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\Cities;
use Food\AppBundle\Entity\Neighbourhood;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAware;

class LocationService extends ContainerAware
{

    public function __construct()
    {

    }

    /**
     * Writes city to session
     *
     * @param string $city
     * @param integer $id
     */
    public function setCityOnlyToSession($city, $id = 0)
    {
        $returner = array();
        $returner['not_found'] = true;
        $returner['street_found'] = false;
        $returner['address_found'] = false;
        $returner['city'] =  $city;
        $returner['address'] = $returner['city'];
        $returner['city_only'] = true;
        if ($id > 0) {
            $returner['city_id'] = intval($id);
        }
        $this->setLocationToSession($returner);
    }

    /**
     * Parse City, Neighbourhood, address and writes to session
     * Works only with parameter: maps_enabled = false
     *
     * @param Cities $city
     * @param Neighbourhood $neighbourhood
     * @param string $address
     *
     * @return array
     */
    public function parseParamsToLocation(Cities $city = null, Neighbourhood $neighbourhood = null, $address = null)
    {
        $locData = $this->getLocationFromSession();
        $locData['not_found'] = true;
        $locData['found'] = false;
        $locData['street_found'] = false;
        $locData['address_found'] = false;
        $locData['city_only'] = false;

        if (!$this->container->getParameter('maps_enabled')) {
            $locData['not_found'] = false;
            $locData['found'] = true;
            if (!is_null($address)) {
                $locData['address_found'] = true;
                $locData['address'] = $address;
                $locData['address_orig'] = $address;
            }

            if ($neighbourhood) {
                $locData['city_id'] = $neighbourhood->getCities()->getId();
                $locData['city'] = $neighbourhood->getCities()->getName();
                $locData['neighbourhood_id'] = $neighbourhood->getId();
                $locData['neighbourhood'] = $neighbourhood->getName();
            } elseif ($city) {
                $locData['city_id'] = $city->getId();
                $locData['city'] = $city->getName();
            }
        }

        $this->setLocationToSession($locData);

        return $locData;
    }

    /**
     * sets user address to session
     * @params User $user
     *
     * @return $locationData
     */
    public function setLocationFromUser(User $user)
    {
        $userAddress = $user->getDefaultAddress();
        $locationData = $this->getLocationFromSession();
        $locationData['not_found'] = true;
        $locationData['found'] = false;
        $locationData['street_found'] = false;
        $locationData['address_found'] = false;
        $locationData['city_only'] = false;
        $locationData['address_found'] = true;
        if ($userAddress) {
            $locationData['city'] = $userAddress->getCity();
            $locationData['address'] = $userAddress->getAddress();
            $locationData['address_orig'] = $userAddress->getAddress();
        }

        $this->setLocationToSession($locationData);
        return $locationData;
    }

    /**
     * Writes location data to session
     *
     * @param array $location
     */
    public function setLocationToSession($location)
    {
        $this->container->get('session')->set('location', $location);
    }

    /**
     * Reads location data from session
     *
     * @return array
     */
    public function getLocationFromSession()
    {
        return $this->container->get('session')->get('location');
    }
}
