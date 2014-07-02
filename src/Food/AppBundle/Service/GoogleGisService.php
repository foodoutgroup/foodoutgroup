<?php
namespace Food\AppBundle\Service;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Symfony\Component\DependencyInjection\ContainerAware;
use Curl;

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
     * @param $address
     * @return \stdClass
     */
    public function getPlaceData($address)
    {
        $addressSplt = explode("-", $address);
        if (sizeof($addressSplt) > 1) {
            $tmp = substr($addressSplt[1], 0, 1);
            if ($tmp == intval($tmp)) {
                $address = $addressSplt[0];
            } else {
                // Nieko nekeiciam
            }
        }
        $resp = $this->getCli()->get(
            $this->container->getParameter('google.maps_geocode'),
            array(
                'address' => $address.', Lithuania',
                'sensor' => 'true',
                'key' => $this->container->getParameter('google.maps_server_api')
            )
        );

        return json_decode($resp->body);
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
                    if (in_array('locality', $addr->types) && in_array('political', $addr->types) && $addr->short_name == $city) {
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
        if( !empty( $location->results[0]) && in_array('street_address', $location->results[0]->types)) {
            $returner['not_found'] = false;
            $returner['street_found'] = true;
            $returner['address_found'] = true;
            $returner['street_nr'] =  $location->results[0]->address_components[0]->long_name;
            $returner['street'] =  $this->__getStreet($location->results[0]->address_components);
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
                $returner['city'] =  $location->results[0]->address_components[1]->long_name;
                $returner['address'] = $returner['street'];
                $returner['lat'] = $location->results[0]->geometry->location->lat;
                $returner['lng'] = $location->results[0]->geometry->location->lng;
            }
        }
        $this->setLocationToSession($returner);
        return $returner;
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

    private function __getStreet($results)
    {
        foreach ($results as $res) {
            if (in_array('route', $res->types)) {
                return $res->long_name;
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
}