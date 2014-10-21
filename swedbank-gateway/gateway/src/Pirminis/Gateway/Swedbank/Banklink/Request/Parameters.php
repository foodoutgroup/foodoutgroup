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
    protected $exceptional_params = ['price'];

    public function set($name, $value)
    {
        if (false === array_search($name, $this->mandatory_params, true)) {
            throw new \InvalidArgumentException('Cannot set parameter.');
        }

        if (in_array($name, $this->exceptional_params)) {
            if ($name == 'transaction_datetime') {
                $this->params[$name] = date('Ymd H:i:s', strtotime($value));
            }

            if ($name == 'price') {
                $this->params[$name] = str_replace('00.', '0.', $value);
            }
        } else {
            $this->params[$name] = $value;
        }

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
