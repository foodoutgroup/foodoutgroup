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

    public function parseLocation($location = [], $flat = null)
    {
        $locationData = null;
        if($location != null && is_array($location)) {
            if (isset($location['flat']) && !$flat) { $flat = $location['flat'] ; }

            if(!is_null($flat) || (isset($location['output']) && !is_null($flat = $this->parseFlat($location['output'])))) {
                preg_match('/(.*?\s+\d{1,3}(\pL|\s\pL)?)([-|\s]{0,4}[\d\pL]{0,3})(.*)$/ium', $location['output'], $response);
                $location['output'] = str_replace($response[3], "", $location['output']);
            }

            $locationData = [
                'id' => isset($location['id']) ? $location['id'] : null,
                'output' => isset($location['output']) ? $location['output'] : null,
                'country' => isset($location['country']) ? $location['country'] : null,
                'city' => isset($location['city']) ? $location['city'] : null,
                'city_id' => null,
                'street' => isset($location['street']) ? $location['street'] : null,
                'house' => isset($location['house']) ? $location['house'] : null,
                'flat' => $flat,
                'latitude' => isset($location['latitude']) ? $location['latitude'] : null,
                'longitude' => isset($location['longitude']) ? $location['longitude'] : null,
                'precision' => 0,
            ];

            if($locationData['city'] != null) {
                $cityObj = $this->em->getRepository('FoodAppBundle:City')->getByName($locationData['city']);
                if($cityObj) {
                    $locationData['city_id'] = $cityObj->getId();
                }
            }

            $locationData['precision'] = $this->precision($locationData);
        }

        return $locationData;
    }
    
    /**
     * @param array $location
     * @param null $flat
     * @return LocationService
     * @internal param bool $writeToSession
     */
    public function set($location = [], $flat = null)
    {
        $dataParsed = $this->parseLocation($location, $flat);

        if($dataParsed) {
            $this->session->set('location', $dataParsed);
        }

        return $this;
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

    public function clear()
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

        return json_decode((new \Curl())->get($this->container->getParameter('geo_provider').'/geocode', array_merge($defaultParams, $params))->body, true);
    }

    public function finishUpData($response = [])
    {

        if($response && isset($response['success']) && $response['success']) {
            $response = $this->parseLocation($response['detail']);
        } else {
            $response = null;
        }

        return $response;
    }

    public function findByHash($hash)
    {
        return $this->finishUpData($this->getGeoCodeCurl(['hash' => $hash]));
    }

    public function parseFlat($address)
    {
        $flat = null;
        if (!empty($address)) {
            preg_match('/(([0-9]{1,3}\s?[a-z]?)[-|\s]{0,4}([\d\w]{0,3}))/i', $address, $addrData);
            if (isset($addrData[0])) {
                $flat = (!empty($addrData[3]) ? $addrData[3] : null);
            }
        }
        return $flat;
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
        return $this->saveAddressFromArrayToUser($this->get(), $user);
    }

    public function saveAddressFromArrayToUser(array $current, User $user)
    {
        $ua = new UserAddress();

        if(!isset($current['origin']) && isset($current['output'])){
            $current['origin'] = $current['output'];
        }

        if(!isset($current['flat'])) {
            $current['flat'] = null;
        }

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
        $ua->setAddressId(isset($current['id']) ? $current['id'] : (isset($current['hash']) ? $current['hash'] : md5($current['origin'])));
        $ua->setDefault(1);
        $this->em->persist($ua);
        $this->em->flush();

        return $ua;
    }
}
