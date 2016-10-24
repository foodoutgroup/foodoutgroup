<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway;

trait RedirectDecorator
{
    protected function handleRedirect($id, $locale)
    {
        // services
        $gateway = $this->get('pirminis_banklink_gateway');

        // get order
        $order = $this->findOrder($id);
        $locale = $locale ? $locale : 'lt';

        // configuration
        $options = array('order_id' => substr($order->getId() . '_' . time(),
                                              0,
                                              16),
                         'price' => (string)round($order->getTotal() * 100),
                         'email' => $order->getUser()->getEmail(),
                         'transaction_datetime' => date('Y-m-d H:i:s'),
                         'comment' => 'Foodout.lt uzsakymas #' . $order->getId(),
                         'success_url' => $this->getSuccessUrl($locale),
                         'failure_url' => $this->getFailureUrl($locale),
                         'language' => $locale);

        switch ($locale) {
            case 'lt':
                $options['service_type'] = 'LIT_BANK';
                break;
            case 'lv':
                $options['service_type'] = 'LTV_BANK';
                $options['comment'] = 'Foodout.lv pasūtījums #' . $order->getId();
                break;
            case 'ee':
                $options['service_type'] = 'EST_BANK';
                break;
        }

        $gateway->set_options($options);

        $form = $gateway->form_for('swedbank');
        $view = 'FoodOrderBundle:Payments:' .
                'swedbank_gateway/redirect.html.twig';
        $data = ['form' => $form->createView()];

        return $this->render($view, $data);
    }
}
