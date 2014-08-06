<?php

namespace Food\ApiBundle\Common;

use Symfony\Component\HttpFoundation\Request;

class JsonRequest
{
    private $requestParams;
    public function __construct(Request $request)
    {
        $body = $request->getContent();

        if (!empty($body)) {
            $this->requestParams = json_decode($body, true);
        } else {
            $this->requestParams = array();
        }
    }

    public function get($key, $default = null)
    {
        if (!empty($this->requestParams[$key])) {
            return $this->requestParams[$key];
        } else {
            return $default;
        }
    }
}