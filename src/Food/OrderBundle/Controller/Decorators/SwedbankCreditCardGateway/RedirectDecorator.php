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
        $options = ['country' => 'LT',
                    'surname' => $order->getUser()->getLastname(),
                    'name' => $order->getUser()->getFirstname(),
                    'telephone' => $order->getUser()->getPhone(),
                    'email' => $order->getUser()->getEmail(),
                    'ip' => !empty($_SERVER['REMOTE_ADDR']) ?
                            $_SERVER['REMOTE_ADDR'] : '',
                    'order_id' => substr($order->getId() . '_' . time(),
                                  0,
                                  16),
                    'price' => sprintf('%.2f', $order->getTotal()),
                    // 'price' => sprintf('%.2f', '0.01'),
                    'transaction_datetime' => date('Y-m-d H:i:s'),
                    'comment' => 'Foodout.lt uzsakymas #' . $order->getId(),
                    'return_url' => $this->getReturnUrl(),
                    'expiry_url' => $this->getExpiryUrl()];
        $gateway->set_options($options);

        return new RedirectResponse($gateway->redirect_url('swedbank'));
    }
}
