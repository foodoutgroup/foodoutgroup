<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;

class LatencyAdminController extends Controller
{
    public function listAction()
    {
        $request = $this->get('request');
        $orderRepo = $this->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $placeRepo = $this->get('doctrine')->getRepository('FoodDishesBundle:Place');

        $dateFrom = new \DateTime($request->get('date_from', '-1 month'));
        $dateTo = new \DateTime($request->get('date_to', 'now'));
        $groupDay = $request->get('group_day', false);

        $places = $request->get('place', array());
        $placesForFilter = $placeRepo->findBy(
            array('active' => 1),
            array('name' => 'ASC')
        );

        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();

            if (!empty($user)) {
                $placeId = $user->getPlace()->getId();

                if (!empty($placeId)) {
                    $places = array($placeId);
                    $placesForFilter = $placeRepo->findBy(
                        array('id' => $placeId, 'active' => 1),
                        array('name' => 'ASC')
                    );
                }
            }
        }

        $stats = $orderRepo->getLatencyReport($dateFrom, $dateTo, $places, $groupDay);

        foreach ($stats as &$place) {
            // TODO - fix dates if group by day :D
            $place['slowest'] = array();
            $place['slowest']['order_accepted'] = $orderRepo->getSlowestOrderForEvent('order_accepted', $place['place_id'], $dateFrom, $dateTo);
            $place['slowest']['order_finished'] = $orderRepo->getSlowestOrderForEvent('order_finished', $place['place_id'], $dateFrom, $dateTo);
            $place['slowest']['order_assigned'] = $orderRepo->getSlowestOrderForEvent('order_assigned', $place['place_id'], $dateFrom, $dateTo);
            $place['slowest']['order_pickedup'] = $orderRepo->getSlowestOrderForEvent('order_pickedup', $place['place_id'], $dateFrom, $dateTo);
            $place['slowest']['order_completed'] = $orderRepo->getSlowestOrderForEvent('order_completed', $place['place_id'], $dateFrom, $dateTo);
        }

        return $this->render(
            'FoodReportBundle:Report:latency_report.html.twig',
            array(
                'stats' => $stats,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'placesSelected' => $places,
                'places' => $placesForFilter,
                'groupDay' => $groupDay,
            )
        );
    }
}
