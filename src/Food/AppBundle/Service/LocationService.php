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
        $returner['address'] = $city;
        $returner['city_only'] = true;
        $returner['city_id'] = intval($id);

        $this->setLocationToSession($returner);
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
        $locationData['city'] = null;
        $locationData['city_id'] = 0;
        $locationData['address_found'] = true;
        if ($userAddress) {

            if($cityObj = $userAddress->getCityId()) {
                $locationData['city'] = $cityObj->getTitle();
                $locationData['city_id'] = $cityObj->getId();
            }

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
