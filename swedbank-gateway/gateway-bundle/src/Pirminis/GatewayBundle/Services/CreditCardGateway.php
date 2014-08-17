<?php

namespace Pirminis\GatewayBundle\Services;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;
use Pirminis\Gateway\Swedbank\FullHps\Request;
use Pirminis\Gateway\Swedbank\FullHps\Response;
use Pirminis\Gateway\Swedbank\FullHps\TransactionQuery\Request as TransRequest;
use Pirminis\Gateway\Swedbank\Banklink\Sender;

class CreditCardGateway
{
    const DTS_REFERENCE = 'dts_reference';

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
               ->set('transaction_datetime',
                     $this->options['transaction_datetime'])
               ->set('comment', $this->options['comment'])
               ->set('return_url', $this->options['return_url'])
               ->set('expiry_url', $this->options['expiry_url'])
        ;

        $request = new Request($params);
        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        return $response->redirect_url();
    }

    public function is_successful_payment($bank, SymfonyRequest $request)
    {
        $config = $this->config[$bank];

        $request = new TransRequest($config['vtid'],
                                    $config['password'],
                                    $request->query
                                            ->get(static::DTS_REFERENCE));
        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        if ($response->is_authenticated()) {
            $request = new TransRequest($config['vtid'],
                                        $config['password'],
                                        $response->dc_reference());
            $sender = new Sender($request->xml());
            $response = new Response($sender->send());

            return $response->query_succeeded();
        }

        return false;
    }

    public function order_id($bank, SymfonyRequest $request)
    {
        $config = $this->config[$bank];

        $request = new TransRequest($config['vtid'],
                                    $config['password'],
                                    $request->query
                                            ->get(static::DTS_REFERENCE));
        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        if ($response->is_authenticated()) {
            $request = new TransRequest($config['vtid'],
                                        $config['password'],
                                        $response->dc_reference());
            $sender = new Sender($request->xml());
            $response = new Response($sender->send());

            return $response->query_merchant_reference();
        }

        return null;
    }
}
