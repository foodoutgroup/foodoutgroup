<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway;

use Symfony\Component\HttpFoundation\RedirectResponse;

trait RedirectDecorator
{
    protected function handleRedirect($id)
    {
        // services
        $gateway = $this->get('pirminis_credit_card_gateway');

        // get order
        $order = $this->findOrder($id);

        // configuration
        $options = ['order_id' => substr($order->getId() . '_' . time(),
                                  0,
                                  16),
                    'price' => sprintf('%.2f', $order->getTotal()),
                    // 'price' => sprintf('%.2f', '0.01'),
                    'transaction_datetime' => date('Y-m-d H:i:s'),
                    'comment' => 'no comment',
                    'return_url' => $this->getReturnUrl(),
                    'expiry_url' => $this->getExpiryUrl()];
        $gateway->set_options($options);

        return new RedirectResponse($gateway->redirect_url('swedbank'));
    }
}
