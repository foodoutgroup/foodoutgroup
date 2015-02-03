<?php

namespace Food\OrderBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;

class OrderAdminController extends Controller
{
    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function sendInvoiceAction($id = null)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($id);

        $orderSfSeries = $order->getSfSeries();
        if (empty($orderSfSeries)) {
            $orderService->setInvoiceDataForOrder();
        }

        $this->get('food.invoice')->addInvoiceToSend($order);

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('admin.order.invoice_added_for_send', array(), 'SonataAdminBundle')
        );

        return $this->redirect(
            $this->generateUrl('admin_food_order_order_list')
        );
    }
}
