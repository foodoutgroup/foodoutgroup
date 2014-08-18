<?php

namespace Pirminis\Gateway\Swedbank\Banklink\Request;

class Parameters
{
    protected $params;
    protected $mandatory_params = ['client',
                                   'password',
                                   'order_id',
                                   'price',
                                   'email',
                                   'transaction_datetime',
                                   'comment',
                                   'success_url',
                                   'failure_url',
                                   'language'];

    public function set($name, $value)
    {
        if (false === array_search($name, $this->mandatory_params, true)) {
            throw new \InvalidArgumentException('Cannot set parameter.');
        }

        $this->params[$name] = $value;

        return $this;
    }

    public function get($name)
    {
        if (false === array_search($name, $this->mandatory_params, true)) {
            throw new \InvalidArgumentException('Cannot get parameter.');
        }

        return $this->params[$name];
    }

    public function mandatory_params()
    {
        return $this->mandatory_params;
    }
}
