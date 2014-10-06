<?php

namespace Food\OrderBundle\Controller\Decorators\Nordea;

use Food\OrderBundle\Form\NordeaBanklinkType;

trait RedirectDecorator
{
    public function handleRedirect($id)
    {
        // services
        $nordea = $this->get('food.nordea_banklink');
        $factory = $this->get('form.factory');

        // params from config
        $rcvId = $this->container->getParameter('nordea.banklink.rcv_id');

        // get order
        $order = $this->findOrder($id);

        // nordea banklink type
        $options = $this->getOptions($order, $rcvId);
        $type = new NordeaBanklinkType($options);

        // redirect form
        $options = ['action' => $nordea->getBankUrl(), 'method' => 'POST'];
        $form = $factory->createNamed('', $type, null, $options);

        // update form with MAC
        $this->updateFormWithMAC($form, $nordea);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/redirect.html.twig';

        // data
        $data = ['form' => $form->createView()];

        return [$view, $data];
    }
}
