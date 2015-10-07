<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;

class DriverLatencyAdminController extends Controller
{
    public function listAction()
    {
        $request = $this->get('request');
        /*$placeRepo = $this->get('doctrine')->getRepository('FoodDishesBundle:Place');

        $dateFrom = new \DateTime($request->get('date_from', '-1 month'));
        $dateTo = new \DateTime($request->get('date_to', 'now'));
        $groupDay = $request->get('group_day', false);

        $places = $request->get('place', array());
        $placesForFilter = $placeRepo->findBy(
            array('active' => 1),
            array('name' => 'ASC')
        );*/

//        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
//            $user = $this->getUser();
//
//            if (!empty($user)) {
//                $placeId = $user->getPlace()->getId();
//
//                if (!empty($placeId)) {
//                    $places = array($placeId);
//                    $placesForFilter = $placeRepo->findBy(
//                        array('id' => $placeId, 'active' => 1),
//                        array('name' => 'ASC')
//                    );
//                }
//            }
//        }

        $stats = $this->get('food.report')->calculateDriverLatencyLastMonth();

        return $this->render(
            'FoodReportBundle:Report:driver_latency_report.html.twig',
            array(
                'stats' => $stats
            )
        );
    }
}
