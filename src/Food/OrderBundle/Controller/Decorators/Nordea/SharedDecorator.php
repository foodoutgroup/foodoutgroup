<?php

namespace Food\OrderBundle\Controller\Decorators\Nordea;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Food\OrderBundle\Service\Events\BanklinkEvent;

trait SharedDecorator
{
    protected function verify(Request $request, $orderService, $order)
    {
        // services
        $nordea = $this->get('food.nordea_banklink');
        $dispatcher = $this->get('event_dispatcher');

        // prepare data
        $data = array_merge($request->request->all(), $request->query->all());

        // banklink log
        $event = new BanklinkEvent();
        $event->setOrderId($order ? $order->getId() : 0);
        $event->setQuery(var_export($request->query->all(), true));
        $event->setRequest(var_export($request->request->all(), true));

        $dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE, $event);

        // verify
        $verified = $nordea->verify($data);

        return $verified && !empty($data['RETURN_PAID']);
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

    protected function getOptions($order, $rcvId)
    {
        return ['stamp' => $order->getId(),
                'rcv_id' => $rcvId,
                'amount' => sprintf('%.2f', $order->getTotal()),
                // 'amount' => sprintf('%.2f', 0.01),
                'ref' => $order->getId(),
                'msg' => 'Foodout.lt uzsakymas #' . $order->getId(),
                'return_url' => $this->getReturnUrl(),
                'cancel_url' => $this->getCancelUrl(),
                'reject_url' => $this->getRejectUrl(),
                'mac' => ''];
    }

    protected function getReturnUrl()
    {
        $url = $this->generateUrl('nordea_banklink_return', [], true);
        return $this->fixUrl($url);
    }

    protected function getCancelUrl()
    {
        $url = $this->generateUrl('nordea_banklink_cancel', [], true);
        return $this->fixUrl($url);
    }

    protected function getRejectUrl()
    {
        $url = $this->generateUrl('nordea_banklink_reject', [], true);
        return $this->fixUrl($url);
    }

    protected function fixUrl($value)
    {
        return str_replace('http://', 'https://', $value);
    }
}
