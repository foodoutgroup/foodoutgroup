<?php

namespace Pirminis\GatewayBundle\Services;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;
use Pirminis\Gateway\Swedbank\FullHps\Request;
use Pirminis\Gateway\Swedbank\FullHps\Response;
use Pirminis\Gateway\Swedbank\Banklink\Sender;

class CreditCardGateway
{
    protected $config;
    protected $options;
    protected $redirect_url;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function set_options(array $options)
    {
        $this->options = $options;
    }

    public function redirect_url($bank)
    {
        $config = $this->config[$bank];

        $params = new Parameters();
        $params->set('client', $config['vtid'])
               ->set('password', $config['password'])
               ->set('order_id', $this->options['order_id'])
               ->set('price', $this->options['price'])
               ->set('transaction_datetime', $this->options['transaction_datetime'])
               ->set('comment', $this->options['comment'])
               ->set('return_url', $this->options['return_url'])
               ->set('expiry_url', $this->options['expiry_url'])
        ;

        $request = new Request($params);
        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        return $response->redirect_url();
    }
}
