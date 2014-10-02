<?php

namespace Pirminis\GatewayBundle\Services;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\Form\Forms;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Pirminis\Gateway\Swedbank\Banklink\Request;
use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\TransactionQuery\Request as TransQuery;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Pirminis\Gateway\Swedbank\Banklink\Response;
use Pirminis\Gateway\Swedbank\Banklink\Form;
use Food\OrderBundle\Service\Events\BanklinkEvent;

class BanklinkGateway
{
    const DPG_REFERENCE_ID = 'DPGReferenceId';
    const TRANSACTION_ID = 'TransactionId';

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

    public function form_for($bank)
    {
        if (empty($this->config[$bank])) {
            throw \InvalidArgumentException('Please specify $bank.');
        }

        $form = $this->form($this->config[$bank], $this->options);
        $form_fields = $form->form_fields();

        if (empty($form_fields)) {
            throw new \RuntimeException('We did not get redirect response' .
                                        'from bank.');
        }

        $factory = Forms::createFormFactory();
        $form_builder = $factory->createNamedBuilder('');

        // add payment fields
        foreach ($form_fields as $name => $value) {
            $form_builder->add($name, 'hidden', ['data' => $value]);
        }

        // add submit button. important: submit input name cannot be "submit"!
        $form_builder->add('swedbank', 'submit');

        // set action and method
        $form_builder->setMethod('POST');
        $form_builder->setAction($form->redirect_url());

        return $form_builder->getForm();
    }

    public function is_authorized($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_authorized();
    }

    public function is_redirect($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_redirect();
    }

    public function requires_investigation($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->requires_investigation();
    }

    public function is_error($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_error();
    }

    public function is_cancelled($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_cancelled();
    }

    public function communication_error($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->communication_error();
    }

    public function order_id($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response ? $response->order_id() : null;
    }

    public function is_event($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_event();
    }

    public function is_event_authorized($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_event_authorized();
    }

    public function event_requires_investigation($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->event_requires_investigation();
    }

    public function is_event_cancelled($bank, SymfonyRequest $request)
    {
        $response = $this->transaction_query_response($bank, $request);

        return $response->is_event_cancelled();
    }

    protected function form(array $config, array $options)
    {
        // create request
        $params = new Parameters();
        $params->set('client', $config['vtid'])
               ->set('password', $config['password'])
               ->set('order_id', $options['order_id'])
               ->set('price', $options['price'])
               ->set('email', $options['email'])
               ->set('transaction_datetime', $options['transaction_datetime'])
               ->set('comment', $options['comment'])
               ->set('success_url', $options['success_url'])
               ->set('failure_url', $options['failure_url'])
               ->set('language', $options['language']);

        $request = new Request($params);

        // for logging purposes
        $event = new BanklinkEvent((int)$params->get('order_id'), null, $request->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_REQUEST, $event);

        // create sender
        $sender = new Sender($request->xml());

        // send request and create response
        $response = new Response($sender->send());

        // for logging purposes
        $event = new BanklinkEvent((int)$params->get('order_id'), null, $response->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE, $event);

        if (!$response->is_redirect()) return null;

        // create form (NOT symfony related) object
        $form = new Form($response->dom(), $response->redirect_url());

        return $form;
    }

    protected function transaction_query_response($bank,
                                                  SymfonyRequest $symfonyRequest)
    {
        if (empty($this->config[$bank])) return $null;
        if (null === $symfonyRequest->query->get(static::DPG_REFERENCE_ID)) {
            return null;
        }

        $config = $this->config[$bank];

        // create transaction query request
        $request = new TransQuery($config['vtid'],
                                  $config['password'],
                                  $symfonyRequest->query
                                          ->get(static::DPG_REFERENCE_ID));

        // for logging purposes
        $event = new BanklinkEvent(
            (int)$symfonyRequest->query->get(static::TRANSACTION_ID),
            null,
            $request->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_QUERY_REQUEST,
                                    $event);

        // create sender
        $sender = new Sender($request->xml());

        // send request and create response
        $response = new Response($sender->send());

        // for logging purposes
        $event = new BanklinkEvent(
            (int)$symfonyRequest->query->get(static::TRANSACTION_ID),
            null,
            $response->xml());
        $this->dispatcher->dispatch(BanklinkEvent::BANKLINK_QUERY_RESPONSE,
                                    $event);

        return $response;
    }
}
