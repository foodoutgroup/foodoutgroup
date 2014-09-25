<?php

namespace Food\OrderBundle\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Food\OrderBundle\Form\NordeaBanklinkType;
use Food\OrderBundle\Service\Events\BanklinkEvent;

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
                    // 'amount' => sprintf('%.2f', 0.01),
                    'ref' => $order->getId(),
                    'msg' => 'Foodout.lt uzsakymas #' . $order->getId(),
                    'return_url' => str_replace(
                        'http://',
                        'https://',
                        $this->generateUrl('nordea_banklink_return',
                                           [],
                                           true)),
                    'cancel_url' => str_replace(
                        'http://',
                        'https://',
                        $this->generateUrl('nordea_banklink_cancel',
                                           [],
                                           true)),
                    'reject_url' => str_replace(
                        'http://',
                        'https://',
                        $this->generateUrl('nordea_banklink_reject',
                                           [],
                                           true)),
                    'mac' => ''];
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
        $data['form'] = $form->createView();

        return [$view, $data];
    }

    public function handleReturnAction(Request $request)
    {
        // services
        $orderService = $this->container->get('food.order');

        // get order. we must use $orderService to find order
        $orderId = (int)$request->query->get('RETURN_REF', 0);
        $order = $orderService->getOrderById($orderId);

        // verify
        $verified = $this->verify($request, $orderService, $order);

        // template
        $view = 'FoodOrderBundle:Payments:' .
                'nordea_banklink/something_wrong.html.twig';


        if ($verified) {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/success.html.twig';

            // success log
            $orderService->setPaymentStatus(
                $orderService::$paymentStatusComplete,
                'Nordea banklink billed payment');
            $orderService->saveOrder();
            $orderService->informPlace();
            $orderService->deactivateCoupon();
        } else {
            $view = 'FoodOrderBundle:Payments:' .
                    'nordea_banklink/fail.html.twig';

            // fail log
            $orderService->logPayment(
                $order,
                'Nordea banklink payment failed',
                'Nordea banklink failed in Nordea',
                $order
            );

            $orderService->setPaymentStatus(
                $orderService::$paymentStatusCanceled,
                'User failed payment in Nordea banklink');
        }

        $data = [];
        return [$view, $data];
    }

    public function handleCancelAction(Request $request)
    {
        return $this->handleReturnAction($request);
    }

    public function handleRejectAction(Request $request)
    {
        return $this->handleCancelAction($request);
    }

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
}
