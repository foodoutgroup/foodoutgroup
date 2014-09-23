<?php

namespace Food\OrderBundle\Controller\Traits;

use Symfony\Component\Form\Form;
use Food\OrderBundle\Form\NordeaBanklinkType;

trait NordeaDecorator
{
    public function handleRedirectAction($id, $rcvId)
    {
        // services
        $nordea = $this->get('food.nordea_banklink');
        $factory = $this->get('form.factory');

        // get order
        $order = $this->findOrder($id);

        // nordea banklink type
        $options = ['stamp' => $order->getId(),
                    'rcv_id' => $rcvId,
                    'amount' => sprintf('%.2f', $order->getTotal()),
                    'ref' => $order->getId(),
                    'msg' => 'Foodout.lt uzsakymas #' . $order->getId(),
                    'return_url' => $this->generateUrl('nordea_banklink_return',
                                                       [],
                                                       true),
                    'cancel_url' => $this->generateUrl('nordea_banklink_cancel',
                                                       [],
                                                       true),
                    'reject_url' => $this->generateUrl('nordea_banklink_reject',
                                                       [],
                                                       true),
                    'mac' => ''];
        $type = new NordeaBanklinkType($options);

        // redirect form
        $options = ['action' => $nordea->getTestBankUrl(), 'method' => 'POST'];
        $form = $factory->createNamed('', $type, null, $options);

        // update form with MAC
        $this->updateFormWithMAC($form, $nordea);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/redirect.html.twig';

        // data
        $data['form'] = $form->createView();

        return [$view, $data];
    }

    protected function updateFormWithMAC(Form $form, $nordea)
    {
        // fill array with form data
        $data = [];

        foreach ($form->all() as $child) {
            $data[$child->getName()] = $child->getData();
        }

        // finally update form
        $form->get('MAC')->setData($nordea->getMacSignature($data));
    }

    protected function findOrder($id)
    {
        return $this->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }
}
