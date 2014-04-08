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
    public function groupData($location, $address)
    {
        $returner = array();
        $returner['not_found'] = true;
        $returner['status'] = $location->status;

        if( !empty( $location->results[0]) && in_array('street_address', $location->results[0]->types)) {
            $returner['not_found'] = false;
            $returner['street_nr'] =  $location->results[0]->address_components[0]->long_name;
            $returner['street'] =  $location->results[0]->address_components[1]->long_name;
            $returner['city'] =  $location->results[0]->address_components[2]->long_name;
            $returner['address'] = $returner['street']." ".$returner['street_nr'];
            $returner['address_orig'] = $address;
            $returner['lat'] = $location->results[0]->geometry->location->lat;
            $returner['lng'] = $location->results[0]->geometry->location->lng;
        }

        $this->setLocationToSession($returner);
        return $returner;
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