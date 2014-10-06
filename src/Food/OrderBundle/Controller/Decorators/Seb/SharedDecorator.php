<?php

namespace Food\OrderBundle\Controller\Decorators\Seb;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Form;
use Food\OrderBundle\Service\Banklink\Seb as SebService;
use Food\OrderBundle\Service\Events\BanklinkEvent;

trait SharedDecorator
{
    protected function getOptions($order)
    {
        return ['snd_id' => 'EM0489',
                'curr' => 'LTL',
                'acc' => 'LT197044060007974514',
                'name' => 'UAB Foodout.lt',
                'lang' => 'LIT',
                'stamp' => $order->getId(),
                'amount' => sprintf('%.2f', $order->getTotal()),
                // 'amount' => sprintf('%.2f', 1.0),
                'ref' => $order->getId(),
                'msg' => 'Foodout.lt uzsakymas #' . $order->getId(),
                'return_url' => $this->getReturnUrl()];
    }

    protected function getReturnUrl()
    {
        $url = $this->generateUrl('seb_banklink_return', [], true);
        return $this->fixUrl($url);
    }

    protected function fixUrl($value)
    {
        return str_replace('http://', 'https://', $value);
    }

    protected function logPaidAndFinish($orderService, $order, $cartService)
    {
        // is order already 'complete'? well then.. we have nothing to do here.
        if ($order->getPaymentStatus() ==
            $orderService::$paymentStatusComplete) return;

        $orderService->setPaymentStatus(
            $orderService::$paymentStatusComplete,
            'SEB banklink billed payment');
        $orderService->saveOrder();
        $orderService->informPlace();

        // Jei naudotas kuponas, paziurim ar nereikia jo deaktyvuoti
        $orderService->deactivateCoupon();

        // clear cart after success
        $cartService->clearCart($order->getPlace());
    }

    protected function logProcessingAndFinish($orderService,
                                              $order,
                                              $cartService)
    {
        $orderService->logPayment(
            $order,
            'SEB banklink payment started',
            'SEB banklink payment accepted. Waiting for funds to be billed',
            $order
        );

        // clear cart after success
        $cartService->clearCart($order->getPlace());
    }

    protected function logFailureAndFinish($orderService, $order)
    {
        $orderService->logPayment(
            $order,
            'SEB banklink payment canceled',
            'SEB banklink canceled in SEB',
            $order
        );

        $orderService->setPaymentStatus(
            $orderService::$paymentStatusCanceled,
            'User canceled payment in SEB banklink');
    }

    protected function findOrder($id)
    {
        return $this->container
                    ->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }

    protected function updateFormWithMAC(Form $form, $seb)
    {
        $seb = $this->container->get('food.seb_banklink');

        // fill array with form data
        $data = [];

        foreach ($form->all() as $child) {
            $data[$child->getName()] = $child->getData();
        }

        // generate encoded MAC
        $mac = $seb->sign($seb->mac($data, SebService::REDIRECT_SERVICE),
                          $seb->getPrivateKey());

        // finally update form
        $form->get('VK_MAC')->setData($mac);
    }

    protected function logBanklink($dispatcher, Request $request, $order)
    {
        $event = new BanklinkEvent();
        $event->setOrderId($order ? $order->getId() : 0);
        $event->setQuery(var_export($request->query->all(), true));
        $event->setRequest(var_export($request->request->all(), true));

        $dispatcher->dispatch(BanklinkEvent::BANKLINK_RESPONSE, $event);
    }
}
