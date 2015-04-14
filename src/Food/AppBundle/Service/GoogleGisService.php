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
     * @return \stdClass
     */
    public function getPlaceData($address)
    {
        if (preg_match("/(\d+\w*\s*-\s*\d+)/i", $address, $matches)) {

            $addressSplt = explode("-", $matches[1]);
            $tmp = $addressSplt[0];

            if ($tmp == intval($tmp)) {
                $cityDelimeter = explode(",", $address);
                $address = strstr($address, $matches[1], true) . $tmp;
                $address.= ", ".end($cityDelimeter);
            } else {
                // Nieko nekeiciam
            }
        }

        $cnt = $this->container->get('doctrine')->getRepository('FoodAppBundle:GeoCache')
            ->findOneBy(
                array(
                    'requestAddress' => $address,
                    'requestCountry' => 'Lithuania'
                )
            );

        if (!$cnt || $cnt == null) {
            $resp = $this->getCli()->get(
                $this->container->getParameter('google.maps_geocode'),
                array(
                    'address' => $address.', Lithuania',
                    'sensor' => 'true',
                    'key' => $this->container->getParameter('google.maps_server_api')
                )
            );

            $geoData = new GeoCache();
            $geoData->setRequestAddress($address)
                ->setRequestCountry('Lithuania')
                ->setRequestData($address.', Lithuania')
                ->setRequestDate(new \DateTime("now"))
                ->setRessponseBody($resp->body)
                ->setCounter(1);

            $em = $this->container->get('doctrine')->getManager();
            $em->persist($geoData);
            $em->flush();

            return json_decode($resp->body);
        } else {
            $cnt->setCounter($cnt->getCounter() + 1);
            $em = $this->container->get('doctrine')->getManager();
            $em->flush();
            return json_decode($cnt->getRessponseBody());
        }
    }

    /**
     * @param \stdClass $location
     * @return array
     */
    public function groupData($location, $address, $city)
    {
        if (sizeof($location->results) > 1) {
            foreach ($location->results as $key=>$rezRow) {
                $hasIt = false;
                foreach ($rezRow->address_components as $addr) {
                    if (in_array('locality', $addr->types) && in_array('political', $addr->types) && ($addr->short_name == $city || $addr->short_name = str_replace("ė", "e", $city))) {
                        $hasIt = true;
                    }
                }
                if (!$hasIt) {
                    unset($location->results[$key]);
                }
            }
            $location->results = array_values($location->results);
        }

        $returner = array();
        $returner['not_found'] = true;
        $returner['street_found'] = false;
        $returner['address_found'] = false;
        $returner['status'] = $location->status;

        if( !empty( $location->results[0]) && (in_array('street_address', $location->results[0]->types) || in_array('premise', $location->results[0]->types))) {
            $returner['not_found'] = false;
            $returner['street_found'] = true;
            $returner['address_found'] = true;
            $returner['street_nr'] =  $location->results[0]->address_components[0]->long_name;
            $returner['street'] =  $this->__getStreet($location->results[0]->address_components);
            $returner['street_short'] =  $this->__getStreet($location->results[0]->address_components, true);
            $returner['city'] =  $this->__getCity($location->results[0]->address_components);
            $returner['address'] = $returner['street']." ".$returner['street_nr'];
            $returner['address_orig'] = $address;
            $returner['lat'] = $location->results[0]->geometry->location->lat;
            $returner['lng'] = $location->results[0]->geometry->location->lng;
        } elseif( !empty( $location->results[0]) && in_array('route', $location->results[0]->types)) {
            $res = preg_match('/\d\w{0,}$/i', $address, $rezult);
            if (!empty($rezult)) {
                $crit = $rezult[0];
            } else {
                $crit = "0000";
            }
            $resIs = preg_match('/'.$crit.'/', $location->results[0]->address_components[0]->long_name);

            if ($res == 0 || $res==1 && $resIs == 1) {
                $returner['street_found'] = true;
                $returner['street'] =  $location->results[0]->address_components[0]->long_name;
                $returner['street_short'] =  $location->results[0]->address_components[0]->short_name;
                $returner['city'] =  $location->results[0]->address_components[1]->long_name;
                $returner['address'] = $returner['street'];
                $returner['lat'] = $location->results[0]->geometry->location->lat;
                $returner['lng'] = $location->results[0]->geometry->location->lng;
            }
        }
        $this->setLocationToSession($returner);

        return $returner;
    }

    public function setCityOnlyToSession($city)
    {
        $returner = array();
        $returner['not_found'] = true;
        $returner['street_found'] = false;
        $returner['address_found'] = false;
        $returner['city'] =  $city;
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
            array(
                'latlng' => $lat.','.$lng,
                'key' => $this->container->getParameter('google.maps_server_api')
            )
        );
        $data = json_decode($resp->body);
        $matchIsFound = null;
        foreach ($data->results as $rezRow) {
            if(in_array('street_address', $rezRow->types) || in_array('premise', $rezRow->types)) {
                $matchIsFound = $rezRow;
                break;
            }
        }
        $returner = array();
        if ($matchIsFound!==null) {
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
            array(
                'address' => $street." ".$houseNumber." ".$city.', Lithuania',
                'sensor' => 'true',
                'key' => $this->container->getParameter('google.maps_server_api')
            )
        );

        $data = json_decode($resp->body);
        $matchIsFound = null;

        foreach ($data->results as $rezRow) {
            if(in_array('street_address', $rezRow->types) || in_array('premise', $rezRow->types)) {
                $matchIsFound = $rezRow;
                break;
            }
        }

        $returner = array();

        if ($matchIsFound!==null) {
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