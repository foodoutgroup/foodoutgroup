<?php
namespace Food\AppBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Curl;

class ArcGisService extends ContainerAware {
    /**
     * @var string
     */
    private $_token;

    /**
     * @var timestamp
     */
    private $_tokenExp;

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
     * @return string
     */
    private function getToken()
    {

        if (empty($this->_token) || $this->_tokenExp > date("U") - 30) {
            $this->refreshToken();
        }
        return $this->_token;
    }

    /**
     * @return void
     */
    private function refreshToken()
    {
        $response  = $this->getCli()->get(
            $this->container->getParameter('arc_gis_oauth'),
            array(
                'client_id' => $this->container->getParameter('arc_gis_client_id'),
                'client_secret' => $this->container->getParameter('arc_gis_client_secret'),
                'grant_type' => 'client_credentials',
                'f' => 'pjson'
            )
        );
        $contentData = json_decode($response->body);
        $this->_token = $contentData->access_token;
        $this->_tokenExp = date("U") + $contentData->expires_in;
    }

    /**
     * @param $theText
     * @return stdClass
     */
    public function getCoordsOfPlace($theText)
    {
        $resp = $this->getCli()->get(
            $this->container->getParameter('arc_gis_geocode_single'),
            array(
                'text' => $theText.', Lithuania',
                'f' => 'pjson',
                'token' => $this->getToken(),
                'outFields' => 'AddNum,StName,City'
            )
        );
        return json_decode($resp->body);
    }

}