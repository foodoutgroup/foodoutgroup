<?php

namespace Food\AppBundle\Controller;

use Food\OrderBundle\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderTrackingController extends Controller
{
    /**
     * @param $hash
     * @return Response
     * @throws \Exception
     */
    public function indexAction($hash)
    {
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderByHash($hash);

        if (!$order instanceof Order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $this->render(
            'FoodAppBundle:Tracking:index.html.twig',
            array(
                'order' => $order,
                'OrderTrackingStatus' => $this->getOrderTrackingStatus($orderService->getOrder())
            )
        );
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function orderStatusCheckAction(Request $request)
    {
        $hash = $request->get('hash', null);
        $orderService = $this->get('food.order');
        $order = $orderService->getOrderByHash($hash);

        if (!$order instanceof Order) {
            throw new NotFoundHttpException('Order not found');
        }

        $response = new Response(json_encode($this->getOrderTrackingStatus($order)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * @param $order
     * @return array
     */
    public function getOrderTrackingStatus($order) {

        if (!$order instanceof Order) {
            throw new NotFoundHttpException('Order not found');
        }

        $order_canceled = 0;
        $order_status = $order->getOrderStatus();
        $order_picked = $order->getOrderPicked();

        $canceled_arr = array('canceled', 'failed', 'nav_problems');

        $statusPercentageTable = array(
            // Step1.
            'pre' => 25,
            'preorder' => 25,
            'unapproved' => 25,
            // Step2.
            'new' => 50,
            'accepted' => 50,
            // Step3.
            'delayed' => 75,
            'forwarded' => 75,
            'assigned' => 75,
            'partialy_completed' => 75,
            // Step4.
            'completed' => 100,
            'finished' => 100,
            // Step Canceled - Failed
            'canceled' => 100,
            'failed' => 100,
            'nav_problems' => 100,
        );

        if (in_array($order_status, $canceled_arr)){
            $order_canceled = 1;
        }

        // order picked up by driver
        if ($order_picked && !$order_canceled && $order_status != 'completed' && $order_status != 'finished') {
            return array(75 => $order_canceled);
        }

        // unknown status
        if (!isset($statusPercentageTable[$order_status])) {
            return array(0 => $order_canceled);
        }

        return array($statusPercentageTable[$order_status] => $order_canceled);
    }
}
