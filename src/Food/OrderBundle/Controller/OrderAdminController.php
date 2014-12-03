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
        $order = $this->get('food.order')->getOrderById($id);
        $this->get('food.invoice')->addInvoiceToSend($order);

        return $this->redirect(
            $this->generateUrl('admin_food_order_order_list')
        );
    }
}
