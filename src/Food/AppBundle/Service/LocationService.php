<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocationService extends ContainerAware
{

    /**
     * @var EntityManager
     */
    private $em;
    private $currentCity = null;

    private $session;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->container = $container;
        $this->em = $em;
        $this->session = $this->container->get('session');
    }

    // 5 - all not found
    // 4 - Country found;
    // 3 - Country, city found;
    // 2 - Country, city, street found;
    // 1 - Country, city, street, house found;
    // 0 - Country, city, street, house, longitude, latitude found;

    public function precision($locationData = [])
    {

        $precision = 0;

        if($locationData) {

            if (!$locationData['latitude'] || !$locationData['longitude']) {
                $precision++;
            }

            if (!$locationData['house']) {
                $precision++;
            }

            if (!$locationData['street']) {
                $precision++;
            }

            if (!$locationData['city']) {
                $precision++;
            }

            if (!$locationData['country']) {
                $precision++;
            }

        } else {
            $precision = 5;
        }

        return $precision;
    }

    /**
     * @param City $city
     * @param bool|null $country
     * @param bool|null $street
     * @param bool|null $house
     * @param bool|null $flat
     * @param bool|null $origin
     * @param bool|null $latitude
     * @param bool|null $longitude
     * @return array
     */
    public function set(City $city, $country = false, $street = false, $house = false, $flat = false, $origin = false, $latitude = false, $longitude = false)
    {
        $current = $this->get();

        if(!$current) {
            $current = [];
        }

        $locationData = [
            'precision' => 0,
            'country' => is_null($country) ? null : (isset($current['country']) && !$country ? $current['country'] : $country),
            'city' => $city->getTitle(),
            'city_id' => $city->getId(),
            'street' => is_null($street) ? null : (isset($current['street']) && !$street ? $current['street'] : $street),
            'house' => is_null($house) ? null : (isset($current['house']) && !$house ? $current['house'] : $house),
            'flat' => is_null($flat) ? null : (isset($current['flat']) && !$flat ? $current['flat'] : $flat),
            'latitude' => is_null($latitude) ? null : (isset($current['latitude']) && !$latitude ? $current['latitude'] : $latitude),
            'longitude' => is_null($longitude) ? null : (isset($current['longitude']) && !$longitude ? $current['longitude'] : $longitude),
            'origin' => is_null($origin) ? null : (isset($current['origin']) && !$origin ? $current['origin'] : $origin),
        ];

        $locationData['hash'] = md5($locationData['origin']);
        $locationData['precision'] = $this->precision($locationData);

        $this->session->set('location', $locationData);
        $this->currentCity = $city;
        return $locationData;
    }

    public function setCity(City $city)
    {
        $this->set($city, null,null,null,null,null,false,false);

        return $this->get();
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->session->get('location');
    }
    /**
     * @return null
     */
    public function getCityObj(){
        return $this->currentCity;
    }

    public function clearLocation()
    {
        $this->session->remove('location');
        return $this;
    }

    private function getGeoCodeCurl($params = [])
    {

        $defaultParams = [
            'language' => $this->container->get('request')->getLocale(),
            'types' => 'geocode',
        ];

        $curl = new \Curl();
        return json_decode($curl->get($this->container->getParameter('geo_provider').'/geocode', array_merge($defaultParams, $params))->body, true);
    }

    public function finishUpData($response = [])
    {

        if($response && isset($response['success']) && $response['success']) {

            $response = $response['detail'];

            $response['precision'] = $this->precision($response);
            $response['city_id'] = null;
            $cityObj = $this->container->get('doctrine')->getRepository('FoodAppBundle:City')->getByName($response['city']);
            if($cityObj) {
                $response['city_id'] = $cityObj->getId();
            }
        } else {
            $response = null;
        }

        return $response;
    }

    public function findByHash($hash)
    {
        return $this->finishUpData($this->getGeoCodeCurl(['hash' => $hash]));
    }


    public function findByAddress($address)
    {
        return $this->finishUpData($this->getGeoCodeCurl(['address' => $address]));
    }

    public function findByIp($ipAddress)
    {
        return $this->finishUpData($this->getGeoCodeCurl(['ip' => $ipAddress]));
    }

    public function findByCords($lat, $lng)
    {
        return $this->finishUpData($this->getGeoCodeCurl(['lat' => $lat, 'lng' => $lng]));
    }

    public function saveAddressFromSessionToUser(User $user)
    {
        $current = $this->get();
        $ua = new UserAddress();
        $addressString = $current['street']. ($current['house'] == null || $current['house'] == false ? "" : " ".$current['house'].($current['flat'] == null || $current['flat'] == false  ? "" : " - ".$current['flat'] ));
        $ua->setAddress($addressString);
        $ua->setCityId($this->getCityObj());
        $ua->setUser($user);
        $ua->setCity($current['city']);
        $ua->setCountry($current['country']);
        $ua->setStreet($current['street']);
        $ua->setHouse($current['house']);
        $ua->setFlat($current['flat']);
        $ua->setLat($current['latitude']);
        $ua->setLon($current['longitude']);
        $ua->setOrigin($current['origin']);
        $ua->setAddressId($current['hash']);
        $ua->setDefault(1);
        $this->em->persist($ua);
        $this->em->flush();

        return $ua;
    }

}
