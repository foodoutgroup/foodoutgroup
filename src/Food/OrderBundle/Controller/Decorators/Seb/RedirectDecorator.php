<?php

namespace Food\OrderBundle\Controller\Decorators\Seb;

use Food\OrderBundle\Form\SebBanklinkType;

trait RedirectDecorator
{
    protected function handleRedirect($id)
    {
        $router = $this->container->get('router');
        $factory = $this->container->get('form.factory');
        $seb = $this->container->get('food.seb_banklink');

        // get order
        $order = $this->findOrder($id);

        // seb banklink type
        $options = $this->getOptions($order);
        $type = new SebBanklinkType($options);

        // redirect form
        $options = ['action' => $seb->getBankUrl(), 'method' => 'POST'];
        $form = $factory->createNamed('', $type, null, $options);

        $this->updateFormWithMAC($form, $seb);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'seb_banklink/redirect.html.twig';

        $data['form'] = $form->createView();

        return [$view, $data];
    }
}
