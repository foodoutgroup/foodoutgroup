<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\City;
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

        // 5 - all not found
        // 4 - Country found;
        // 3 - Country, city found;
        // 2 - Country, city, street found;
        // 1 - Country, city, street, house found;
        // 0 - Country, city, street, house, longitude, latitude found;

        $defaultPrecision = 0;

        $locationData = [
            'precision' => $defaultPrecision,
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

        if(!$locationData['latitude'] || !$locationData['longitude']) {
            $defaultPrecision++;
        }

        if(!$locationData['house']) {
            $defaultPrecision++;
        }

        if(!$locationData['street']) {
            $defaultPrecision++;
        }

        if(!$locationData['city']) {
            $defaultPrecision++;
        }

        if(!$locationData['country']) {
            $defaultPrecision++;
        }

        $locationData['precision'] = $defaultPrecision;

        $this->session->set('location', $locationData);
        $this->currentCity = $city;
        return $locationData;
    }

    public function setCity(City $city)
    {
        $this->set($city, null,null,null,null,null,false,false);
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

    public function getByHash($hash)
    {
        $curl = new \Curl();
        return json_decode($curl->get($this->container->getParameter('geo_provider').'/geocode', [
            'hash' => $hash,
            'language' => $this->container->get('request')->getLocale(),
            'types' => 'geocode',
        ])->body, true);

    }

}
