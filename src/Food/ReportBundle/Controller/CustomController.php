<?php

namespace Food\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class CustomController extends Controller
{
    public function cancanAction(Request $request)
    {
        $placesIds = array(210);
        $cancanEmails = array(
            'cancan@foodout.lt',
            'nuolaidos@delano.lt',
            'isvezimai@delano.lt',
            'dispeceres@foodout.lt',
        );

        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');

        $repo = $this->getDoctrine()->getRepository('FoodOrderBundle:Order');
        $orders = $repo->getCompletedOrdersInDateRangeByPlaceId($from, date('Y-m-d', strtotime($to.' +1 day')), $placesIds);

        return $this->render('FoodReportBundle:Custom:cancan.html.twig', array(
            'orders' => $orders,
            'cancanEmails' => $cancanEmails,
            'from' => $from,
            'to' => $to
        ));
    }
}