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
        if ($location != null && is_array($location)) {
            if (isset($location['flat']) && !$flat) { $flat = $location['flat'] ; }
            if(!is_null($flat) || (isset($location['output']) && !is_null($flat = $this->parseFlat($location['output']))) ) {
                preg_match('/(.*?\d{1,3}(\pL|\s\pL)?)([-|\s]{0,4}[\d\pL]{0,3})(.*)$/ium', $location['output'], $response); //TODO: fix this bitch ass reg
                if (isset($response[3])) {
                   $location['outputNoFlat'] = str_replace($response[3], "", $location['output']);
                }
            }

            $locationData = [
                'id' => isset($location['id']) ? $location['id'] : null,
                'output' => isset($location['output']) ? $location['output'] : null,
                'outputNoFlat' => isset($location['outputNoFlat']) ? $location['outputNoFlat'] : null,
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

            if ($locationData['city']) {
                $cityObj = $this->em->getRepository('FoodAppBundle:City')->getByName($locationData['city']);
                if ($cityObj) {
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
                $precision = 1;
            } else if (!$locationData['house']) {
                $precision = 2;
            } else if (!$locationData['street']) {
                $precision = 3;
            } else if (!$locationData['city']) {
                $precision = 4;
            } else if (!$locationData['country']) {
                $precision = 5;
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
        $current = $this->session->get('location');
        // TODO: remove after week (added 2017-06-23)
        if($current && is_array($current)) {
            if(!isset($current['output']) && isset($current['origin'])) {
                $current['output'] = $current['origin'];
            }

            if(!isset($current['id']) && isset($current['hash'])) {
                $current['id'] = $current['hash'];
            }
        }

        return $current;
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
//            'language' => $this->container->get('request')->getLocale(),
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

    public function parseAddrData($address)
    {
        $regxpHouseFlat = '
           /\A\s*
           (?: #########################################################################
               # Option A: [<Addition to address 1>] <House number> <Street name>      #
               # [<Addition to address 2>]                                             #
               #########################################################################
               (?:(?P<a_additional_1>.*?),\s*)? # Addition to address 1
           (?:No\.\s*)?
               (?P<house_flat>\pN+[a-zA-Z]{0,2}(?:\s*[-\/\pP]\s*\pN+[a-zA-Z]?)*) # House number
           \s*,?\s*
               (?P<a_street_name>(?:[a-zA-Z]\s*|\pN\pL{2,}\s\pL)\S[^,#]*?(?<!\s)) # Street name
           \s*(?:(?:[,\/]|(?=\#))\s*(?!\s*No\.)
               (?P<a_additional_2>(?!\s).*?))? # Addition to address 2
           |   #########################################################################
               # Option B: [<Addition to address 1>] <Street name> <House number>      #
               # [<Addition to address 2>]                                             #
               #########################################################################
               (?:(?P<b_additional_1>.*?),\s*(?=.*[,\/]))? # Addition to address 1
               (?!\s*No\.)(?P<b_street_name>[^0-9# ]\s*\S(?:[^,#](?!\b\pN+\s))*?(?<!\s)) # Street name
           \s*[\/,]?\s*(?:\sNo[.:])?\s*
               (?P<b_house_flat>\pN+\s*-?[a-zA-Z]{0,2}(?:\s*[-\/\pP]?\s*\pN+(?:\s*[\-a-zA-Z])?)*|
               [IVXLCDM]+(?!.*\b\pN+\b))(?<!\s) # House number
           \s*(?:(?:[,\/]|(?=\#)|\s)\s*(?!\s*No\.)\s*
               (?P<b_additional_2>(?!\s).*?))? # Addition to address 2
           )
           \s*\Z/xu';

        preg_match($regxpHouseFlat, $address, $addrData);

        return $addrData;

    }


    public function parseFlat($address)
    {
        $flat = null;
        if (!empty($address)) {

            $addrData = $this->parseAddrData($address);

            if (isset($addrData['house_flat']) && $addrData['house_flat'] != '') {
                $matches = preg_split('/([\\\\s@&.?$+-]+)/i', $addrData['house_flat']);
                $flat = (!empty($matches[1]) ? $matches[1] : null);
            } elseif (isset($addrData['b_house_flat']) && $addrData['b_house_flat'] != '')
            {
                $matches = preg_split('/([\\\\s@&.?$+-]+)/i', $addrData['b_house_flat']);
                $flat = (!empty($matches[1]) ? $matches[1] : null);
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

    public function saveAddressFromArrayToUser(array $location, User $user)
    {
        $ua = new UserAddress();

        if($location['city_id']) {
            $cityObj = $this->container->get('doctrine')->getRepository('FoodAppBundle:City')->find($location['city_id']);
            if ($cityObj) {
                $ua->setCityId($cityObj);
            }
        }

        $ua->setUser($user);
        $ua->setCity($location['city']);
        $ua->setCountry($location['country']);
        $ua->setStreet($location['street']);
        $ua->setHouse($location['house']);
        $ua->setFlat($location['flat']);
        $ua->setLat($location['latitude']);
        $ua->setLon($location['longitude']);
        $ua->setOrigin($location['output']);
        $ua->setAddressId($location['id']);
        $ua->setDefault(1);
        $this->em->persist($ua);
        $this->em->flush();

        return $ua;
    }
}
