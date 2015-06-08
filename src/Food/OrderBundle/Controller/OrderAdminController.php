<?php

namespace Food\OrderBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Response;

class OrderAdminController extends Controller
{
    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Exception
     */
    public function sendInvoiceAction($id = null)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderById($id);

        if (!$order->getPlace()->getSendInvoice()) {
            throw new \Exception('Place has disabled invoices - cant send invoice to user');
        }

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

    /**
     * @param null $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function downloadInvoiceAction($id = null)
    {
        $orderService = $this->get('food.order');
        $invoiceService = $this->get('food.invoice');
        $order = $orderService->getOrderById($id);

        $fileName = $invoiceService->getInvoiceFilename($order);
        $file = 'https://s3-eu-west-1.amazonaws.com/foodout-invoice/pdf/'.$fileName;

        $content = file_get_contents($file);

        $response = new Response();

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="'.$fileName);

        $response->setContent($content);

        return $response;
    }
}
