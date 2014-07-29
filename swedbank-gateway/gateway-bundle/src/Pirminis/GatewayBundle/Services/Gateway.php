<?php

namespace Pirminis\GatewayBundle\Services;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\Form\Forms;
use Pirminis\Gateway\Swedbank\Banklink\Request;
use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;
use Pirminis\Gateway\Swedbank\Banklink\TransactionQuery\Request as TransQuery;
use Pirminis\Gateway\Swedbank\Banklink\Sender;
use Pirminis\Gateway\Swedbank\Banklink\Response;
use Pirminis\Gateway\Swedbank\Banklink\Form;

class Gateway
{
    const DPG_REFERENCE_ID = 'DPGReferenceId';

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

        return $response->order_id();
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

        // create sender
        $sender = new Sender($request->xml());

        // send request and create response
        $response = new Response($sender->send());

        if (!$response->is_redirect()) return null;

        // create form (NOT symfony related) object
        $form = new Form($response->dom(), $response->redirect_url());

        return $form;
    }

    protected function transaction_query_response($bank,
                                                  SymfonyRequest $request)
    {
        if (empty($this->config[$bank])) {
            throw \InvalidArgumentException('Please specify $bank.');
        }

        if (null === $request->query->get(static::DPG_REFERENCE_ID)) {
            throw \InvalidArgumentException('Missing key ' .
                                            static::DPG_REFERENCE_ID .
                                            '.');
        }

        $config = $this->config[$bank];

        // create transaction query request
        $request = new TransQuery($config['vtid'],
                                  $config['password'],
                                  $request->query
                                          ->get(static::DPG_REFERENCE_ID));

        // create sender
        $sender = new Sender($request->xml());

        // send request and create response
        $response = new Response($sender->send());

        return $response;
    }
}
