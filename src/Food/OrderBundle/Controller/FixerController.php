<?php

namespace Food\OrderBundle\Controller;

@ini_set('memory_limit', '2048M');
@ini_set('max_execution_time', 600);

use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FixerController extends Controller
{
    /**
     * @param Request $request
     */
    public function noInvoiceAction(Request $request)
    {
        $date = date("Y-m-d", strtotime($request->get('date')));
        $conn = $this->container->get('doctrine')->getConnection();
        $query = "SELECT id, user_id, place_id, order_date, sf_series, sf_number FROM orders WHERE sf_series IS NULL AND sf_number IS NULL AND order_status = 'completed' AND order_date LIKE '".$date."%'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result =  $stmt->fetchAll();
        $oser = $this->container->get('food.order');
        echo "<pre>";
        foreach ($result as $res) {
            $order = $this->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($res['id']);
            echo $order->getId()." : ";
            if ($order->getPlace()->getSendInvoice()
                && !$order->getPlacePointSelfDelivery()
                && $order->getDeliveryType() == OrderService::$deliveryDeliver) {
                // Patikrinam ar sitam useriui reikia generuoti sf
                if (!$order->getUser()->getNoInvoice()) {
                    $oser->setOrder($order);
                    $mustDoNavDelete = $oser->setInvoiceDataForOrder();

                    // Suplanuojam sf siuntima klientui
                    $this->container->get('food.invoice')->addInvoiceToSend($order, $mustDoNavDelete);
                    echo "YES PLACE SF";
                } else {
                    echo "NO USER INVOICE";
                }
            } else {
                echo "NO PLACE SF";
            }
            echo "\n";
        }
        echo "</pre>";
        die("END OF CONTROLLER");
    }

    public function createInvoiceAction(Request $request)
    {
        $id = $request->get('id');
        if (!$id) {
            throw new \InvalidArgumentException('No id setted');
        }

        $order = $this->container->get('doctrine')->getRepository('FoodOrderBundle:Order')->find($id);

        $navService = $this->container->get('food.nav');
        var_dump($navService->createInvoice($order));
        die;
    }
}
