<?php

namespace Pirminis\Gateway\Swedbank\FullHps\Request;

class Parameters
{
    protected $params;
    protected $mandatory_params = ['client',
                                   'password',
                                   'city',
                                   'country',
                                   'shipping_address',
                                   'surname',
                                   'name',
                                   'telephone',
                                   'email',
                                   'ip',
                                   'order_id',
                                   'price',
                                   'transaction_datetime',
                                   'comment',
                                   'return_url',
                                   'expiry_url'];
    protected $exceptional_params = ['transaction_datetime',
                                     'price'];

    public function set($name, $value)
    {
        if (false === array_search($name, $this->mandatory_params, true)) {
            throw new \InvalidArgumentException('Cannot set parameter.');
        }

        if (is_null($value)) $value = '';

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
        if (false === array_search($name, $this->mandatory_params, true) ||
            !isset($this->params[$name]) ||
            is_null($this->params[$name])
        ) {
            throw new \InvalidArgumentException('Cannot get parameter.');
        }

        return $this->params[$name];
    }

    public function mandatory_params()
    {
        return $this->mandatory_params;
    }
}
