<?php

namespace Food\CartBundle\Controller;

use Food\OrderBundle\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class GeneratorController extends Controller
{
    public function generatorAction($oid)
    {
        $ois = $this->get('food.order');
        $ois->generateCsvById($oid);
        return new Response($oid);
    }

    public function generatorByDateAction($from, $to)
    {
        $ois = $this->get('food.order');
        $oids = $this->getDoctrine()->getRepository('FoodOrderBundle:Order')->findBy(
            array(
                'order_status' => OrderService::$status_completed,
                'paymentStatus' => OrderService::$paymentStatusComplete
            )
        );
        foreach ($oids as $oid) {
            $ois->generateCsvById($oid);
        }
        return new Response(sizeof($oids));
    }
}
