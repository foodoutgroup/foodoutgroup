<?php

namespace Food\ApiBundle\Common;

use Symfony\Component\HttpFoundation\Request;

class JsonRequest
{
    public $requestParams;
    public function __construct(Request $request)
    {
        $body = $request->getContent();

        if (!empty($body)) {
            $this->requestParams = json_decode($body, true);
        } else {
            $this->requestParams = array();
        }
    }

    public function has($key){
        return (!empty($this->requestParams[$key]));
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