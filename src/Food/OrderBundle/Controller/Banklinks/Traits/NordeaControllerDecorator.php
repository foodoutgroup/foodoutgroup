<?php

namespace Food\OrderBundle\Controller\Banklinks\Traits;

use Food\OrderBundle\Form\NordeaBanklinkType;
use Symfony\Component\Form\Form;

trait NordeaControllerDecorator
{
    public function handleRedirectAction($id, $rcvId)
    {
        // services
        $nordea = $this->get('food.nordea_banklink');

        // get order
        $order = $this->findOrder($id);

        // nordea banklink type
        $options = ['stamp' => $order->getId(),
                    'rcv_id' => $rcv_id,
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
        $options = ['action' => $seb->getTestBankUrl(), 'method' => 'POST'];
        $form = $factory->createNamed('', $type, null, $options);

        // update form with MAC
        $this->updateFormWithMAC($form);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/redirect.html.twig';

        return [$view, $data];
    }

    protected function updateFormWithMAC(Form $form)
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
