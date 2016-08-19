<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\GeoCache;
use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Symfony\Component\DependencyInjection\ContainerAware;
use Curl;
use Symfony\Component\Form\Tests\Extension\Validator\Type\BaseValidatorExtensionTest;

class GoogleGisService extends ContainerAware
{

    /**
     * @var Curl
     */
    private $_cli;

    public function __construct()
    {

    }

    /**
     * @param \Curl $cli
     */
    public function setCli($cli)
    {
        $this->_cli = $cli;
    }

    /**
     * @return \Curl
     */
    public function getCli()
    {
        if (empty($this->_cli)) {
            $this->_cli = new Curl;
            $this->_cli->options['CURLOPT_SSL_VERIFYPEER'] = false;
            $this->_cli->options['CURLOPT_SSL_VERIFYHOST'] = false;
        }

        return $this->_cli;
    }

    /**
     * @param string $address
     * @param bool $fresh
     *
     * @return \stdClass
     */
    public function getPlaceData($address, $fresh = false)
    {
        if (preg_match("/(\d+\w*\s*-\s*\d+)/i", $address, $matches)) {

            $addressSplt = explode("-", $matches[1]);
            $tmp = $addressSplt[0];

            if ($tmp == intval($tmp)) {
                $cityDelimeter = explode(",", $address);
                $address = strstr($address, $matches[1], true) . $tmp;
                $address .= ", " . end($cityDelimeter);
            } else {
                // Nieko nekeiciam
            }
        }

        // fix for address like Salaspils 18/1
        $address = preg_replace('#[\/][a-z\d]+#i', '', $address);

        if (!$fresh) {
            $geoCache = $this->container->get('doctrine')->getRepository('FoodAppBundle:GeoCache')
                ->findOneBy(
                    [
                        'requestAddress' => $address,
                        'requestCountry' => $this->container->getParameter('country_full')
                    ]
                )
            ;
        }

        if (empty($geoCache)) {
            $geoCache = $this->findAndCache($address);
        } else {
            $geoCache->setCounter($geoCache->getCounter() + 1);
            $em = $this->container->get('doctrine')->getManager();
            $em->flush();
        }

        return json_decode($geoCache->getRessponseBody());
    }

    /**
     * @param string $address
     *
     * @return GeoCache
     */
    public function findAndCache($address)
    {
        $resp = $this->getCli()->get(
            $this->container->getParameter('google.maps_geocode'),
            [
                'address' => $address . ', ' . $this->container->getParameter('country_full'),
                'sensor'  => 'true',
                'key'     => $this->container->getParameter('google.maps_server_api')
            ]
        )
        ;

        $geoCache = new GeoCache();
        $geoCache->setRequestAddress($address)
            ->setRequestCountry($this->container->getParameter('country_full'))
            ->setRequestData($address . ', ' . $this->container->getParameter('country_full'))
            ->setRequestDate(new \DateTime("now"))
            ->setRessponseBody($resp->body)
            ->setCounter(1)
        ;

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($geoCache);
        $em->flush();

        return $geoCache;
    }

    /**
     * @param string $address
     * @param string $city
     *
     * @return array
     */
    public function groupData($address, $city)
    {
        $location = $this->getPlaceData($address . ', ' . $city);
        $returner = $this->parseDataFromLocation($location, $address);

        if ($returner['not_found']) {
            @mail('karolis.m@foodout.lt', 'not found address 1', $address . $city, ['from' => 'info@foodout.lt']);
            $location = $this->getPlaceData($address . ', ' . $city, true);
            $returner = $this->parseDataFromLocation($location, $address);

            if ($returner['not_found']) {
                @mail('karolis.m@foodout.lt', 'not found address 2', $address . $city, ['from' => 'info@foodout.lt']);
            }
        }

        $this->setLocationToSession($returner);

        return $returner;
    }

    public function parseDataFromLocation($location, $address)
    {
        if (sizeof($location->results) > 1) {
            foreach ($location->results as $key => $rezRow) {
                if (!in_array('route', $rezRow->types) && !in_array('street_address', $rezRow->types)) {
                    unset($location->results[$key]);
                }
            }
        }
        $location->results = array_values($location->results);
        $returner = [];
        $returner['not_found'] = true;
        $returner['street_found'] = false;
        $returner['address_found'] = false;
        $returner['status'] = $location->status;

        $miscUtils = $this->container->get('food.app.utils.misc');
        $addressData = $miscUtils->parseAddress($address);
        $returner['flat'] = '';
        if (!empty($addressData)) {
            $returner['flat'] = $addressData['flat'];
        }

        if (!empty($location->results[0]) && (in_array('street_address', $location->results[0]->types) || in_array('premise', $location->results[0]->types))) {
            $returner['not_found'] = false;
            $returner['street_found'] = true;
            $returner['address_found'] = true;
            $returner['street_nr'] = $location->results[0]->address_components[0]->long_name;
            $returner['street'] = $this->__getStreet($location->results[0]->address_components);
            $returner['street_short'] = $this->__getStreet($location->results[0]->address_components, true);
            $returner['city'] = $this->__getCity($location->results[0]->address_components);
            $returner['address'] = $returner['street'] . " " . $returner['street_nr'];
            $returner['address_orig'] = $address;
            $returner['lat'] = $location->results[0]->geometry->location->lat;
            $returner['lng'] = $location->results[0]->geometry->location->lng;
        } elseif (!empty($location->results[0]) && (in_array('route', $location->results[0]->types) || in_array("neighborhood", $location->results[0]->types))) {
            $res = preg_match('/\d\w{0,}$/i', $address, $rezult);
            if (!empty($rezult)) {
                $crit = $rezult[0];
            } else {
                $crit = "0000";
            }
            $resIs = preg_match('/' . $crit . '/', $location->results[0]->address_components[0]->long_name);
            if ($res == 0 || $res == 1 && $resIs == 1) {
                $returner['street_found'] = true;
                $returner['street'] = $location->results[0]->address_components[0]->long_name;
                $returner['street_short'] = $location->results[0]->address_components[0]->short_name;
                $returner['city'] = $location->results[0]->address_components[1]->long_name;
                $returner['address'] = $returner['street'];
                $returner['lat'] = $location->results[0]->geometry->location->lat;
                $returner['lng'] = $location->results[0]->geometry->location->lng;
            }
        }

        return $returner;
    }

    public function setCityOnlyToSession($city)
    {
        $returner = [];
        $returner['not_found'] = true;
        $returner['street_found'] = false;
        $returner['address_found'] = false;
        $returner['city'] = $city;
        $returner['address'] = $returner['city'];
        $returner['city_only'] = true;
        $this->setLocationToSession($returner);
    }

    private function __getCity($results)
    {
        foreach ($results as $res) {
            if (in_array('locality', $res->types) && in_array('political', $res->types)) {
                return $res->long_name;
            }
        }

        return "";
    }

    private function __getStreet($results, $shortVersion = false)
    {
        foreach ($results as $res) {
            if (in_array('route', $res->types)) {
                if ($shortVersion) {
                    return $res->short_name;
                } else {
                    return $res->long_name;
                }
            }
        }

        return "";
    }

    /**
     * @param array $location
     */
    public function setLocationToSession($location)
    {
        $this->container->get('session')->set('location', $location);
    }

    /**
     * @return array
     */
    public function getLocationFromSession()
    {
        return $this->container->get('session')->get('location');
    }

    public function findAddressByCoords($lat, $lng)
    {
        $resp = $this->getCli()->get(
            $this->container->getParameter('google.maps_geocode'),
            [
                'latlng' => $lat . ',' . $lng,
                'key'    => $this->container->getParameter('google.maps_server_api')
            ]
        )
        ;
        $data = json_decode($resp->body);
        $matchIsFound = null;
        foreach ($data->results as $rezRow) {
            if (in_array('street_address', $rezRow->types) || in_array('premise', $rezRow->types)) {
                $matchIsFound = $rezRow;
                break;
            }
        }
        $returner = [];
        if ($matchIsFound !== null) {
            foreach ($matchIsFound->address_components as $cmp) {
                if (in_array('street_number', $cmp->types)) {
                    $returner['house_number'] = $cmp->long_name;
                }
                if (in_array('route', $cmp->types)) {
                    $returner['street'] = $cmp->short_name;
                }
                if (in_array('locality', $cmp->types) && in_array('political', $cmp->types)) {
                    $returner['city'] = $cmp->short_name;
                }
            }
        }

        return $returner;
    }

    public function findAddressByCoordsByStuff($city, $street, $houseNumber)
    {
        $resp = $this->getCli()->get(
            $this->container->getParameter('google.maps_geocode'),
            [
                'address' => $street . " " . $houseNumber . " " . $city . ', ' . $this->container->getParameter('country_full'),
                'sensor'  => 'true',
                'key'     => $this->container->getParameter('google.maps_server_api')
            ]
        )
        ;

        $data = json_decode($resp->body);
        $matchIsFound = null;

        foreach ($data->results as $rezRow) {
            if (in_array('street_address', $rezRow->types) || in_array('premise', $rezRow->types)) {
                $matchIsFound = $rezRow;
                break;
            }
        }

        $returner = [];

        if ($matchIsFound !== null) {
            foreach ($matchIsFound->address_components as $cmp) {
                if (in_array('street_number', $cmp->types)) {
                    $returner['house_number'] = $cmp->long_name;
                }
                if (in_array('route', $cmp->types)) {
                    $returner['street'] = $cmp->short_name;
                }
                if (in_array('locality', $cmp->types) && in_array('political', $cmp->types)) {
                    $returner['city'] = $cmp->short_name;
                }
            }
        }

        return $returner;
    }
}
