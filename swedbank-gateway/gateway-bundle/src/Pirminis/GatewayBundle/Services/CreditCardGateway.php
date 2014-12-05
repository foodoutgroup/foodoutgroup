<?php

namespace Pirminis\GatewayBundle\Services;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pirminis\Gateway\Swedbank\FullHps\Request\Parameters;
use Pirminis\Gateway\Swedbank\FullHps\Request;
use Pirminis\Gateway\Swedbank\FullHps\Response;
use Pirminis\Gateway\Swedbank\FullHps\TransactionQuery\Request as TransRequest;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Food\OrderBundle\Service\Events\BanklinkEvent;

class CreditCardGateway
{
    const DTS_REFERENCE = 'dts_reference';

    protected $dispatcher;
    protected $config;
    protected $options;
    protected $redirect_url;

    public function __construct(EventDispatcherInterface $dispatcher,
                                array $config)
    {
        $this->dispatcher = $dispatcher;
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
               ->set('country', $this->options['country'])
               ->set('surname', $this->options['surname'])
               ->set('name', $this->options['name'])
               ->set('telephone', $this->options['telephone'])
               ->set('email', $this->options['email'])
               ->set('ip', $this->options['ip'])
               ->set('order_id', $this->options['order_id'])
               ->set('price', $this->options['price'])
               ->set('transaction_datetime',
                     $this->options['transaction_datetime'])
               ->set('comment', $this->options['comment'])
               ->set('return_url', $this->options['return_url'])
               ->set('expiry_url', $this->options['expiry_url'])
        ;

        $request = new Request($params);

        // for logging purposes
        $event = new BanklinkEvent((int)$params->get('order_id'), null, $request->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST, $event);

        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        // for logging purposes
        $event = new BanklinkEvent((int)$params->get('order_id'), null, $response->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE, $event);

        return $response->redirect_url();
    }

    public function is_successful_payment($bank, SymfonyRequest $request)
    {
        $config = $this->config[$bank];

        $request = new TransRequest($config['vtid'],
                                    $config['password'],
                                    $request->query
                                            ->get(static::DTS_REFERENCE));

        // for logging purposes
        $event = new BanklinkEvent(
            null,
            null,
            $request->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST,
                                    $event);

        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        // for logging purposes
        $event = new BanklinkEvent(
            null,
            null,
            $response->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE,
                                    $event);

        if ($response->is_authenticated()) {
            $request = new TransRequest($config['vtid'],
                                        $config['password'],
                                        $response->dc_reference());

            // for logging purposes
            $event = new BanklinkEvent(
                null,
                null,
                $request->xml());
            $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST,
                                        $event);

            $sender = new Sender($request->xml());
            $response = new Response($sender->send());

            // for logging purposes
            $event = new BanklinkEvent(
                null,
                null,
                $response->xml());
            $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE,
                                        $event);

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

        // for logging purposes
        $event = new BanklinkEvent(
            null,
            null,
            $request->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST,
                                    $event);

        $sender = new Sender($request->xml());
        $response = new Response($sender->send());

        // for logging purposes
        $event = new BanklinkEvent(
            null,
            null,
            $response->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE,
                                    $event);

        if ($response->is_authenticated()) {
            $request = new TransRequest($config['vtid'],
                                        $config['password'],
                                        $response->dc_reference());

            // for logging purposes
            $event = new BanklinkEvent(
                null,
                null,
                $request->xml());
            $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST,
                                        $event);

            $sender = new Sender($request->xml());
            $response = new Response($sender->send());

            // for logging purposes
            $event = new BanklinkEvent(
                null,
                null,
                $response->xml());
            $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE,
                                        $event);

            return $response->query_merchant_reference();
        }

        return null;
    }
}
