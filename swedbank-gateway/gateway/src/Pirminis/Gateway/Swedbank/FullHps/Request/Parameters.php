<?php

namespace Pirminis\Gateway\Swedbank\FullHps\Request;

class Parameters
{
    protected $params;
    protected $mandatory_params = ['client',
                                   'password',
                                   'order_id',
                                   'price',
                                   'transaction_datetime',
                                   'comment',
                                   'return_url',
                                   'expiry_url'];

    public function set($name, $value)
    {
        if (false === array_search($name, $this->mandatory_params, true) ||
            empty($value)
        ) {
            throw \InvalidArgumentException('Cannot set parameter.');
        }

        $this->params[$name] = $value;

        return $this;
    }

    public function get($name)
    {
        if (false === array_search($name, $this->mandatory_params, true) ||
            !isset($this->params[$name]) ||
            empty($this->params[$name])
        ) {
            throw \InvalidArgumentException('Cannot get parameter.');
        }

        return $this->params[$name];
    }

    public function mandatory_params()
    {
        return $this->mandatory_params;
    }
}
