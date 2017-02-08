<?php
namespace Api\BaseBundle\Response;

use Symfony\Component\HttpFoundation\Response;

class ApplicationXML extends Response
{

    public function __construct($data)
    {
        parent::__construct('', 200, []);

        $this->headers->set('Content-Type', 'application/xml');


        $xml_data = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
        $this->array_to_xml($data, $xml_data);
        $this->setContent($xml_data->asXML());
        
    }

    function array_to_xml( $data, &$xml_data ) {
        foreach( $data as $key => $value ) {
            if( is_numeric($key) ){
                $key = 'item'; //dealing with <0/>..<n/> issues
            }
            if( is_array($value) ) {
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
}