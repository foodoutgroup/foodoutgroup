<?php

namespace Api\BaseBundle\Common;

use Symfony\Component\HttpFoundation\Request;

class JsonRequest
{
    public $requestParams;
    public function __construct(Request $request)
    {
        $body = $request->getContent();

        if($body[0] == "<") {
            $xml = simplexml_load_string($body, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json, true);

            $itemFinal = [];
            foreach ($array['items'] as $item) {
                $additionalFinal = [];
                if(isset($item['additional'])) {
                    foreach ($item['additional'] as $additional) {
                        $additionalFinal[] = $additional;
                    }
                }
                $item['additional'] = $additionalFinal;
                $itemFinal[] = $item;

            }
            $array['items'] = $itemFinal;
            $body = json_encode($array, true);
        }



        if (!empty($body)) {
            $this->requestParams = json_decode($body, true);
        } else {
            $this->requestParams = array();
        }
    }

    public function has($key) {
        return isset($this->requestParams[$key]);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!empty($this->requestParams[$key])) {
            return $this->requestParams[$key];
        } else {
            return $default;
        }
    }
}