<?php

namespace Food\ReportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class StatsController extends Controller
{
    public function dashboardStatsAction()
    {
        if ($this->getUser() && $this->getUser()->hasRole('ROLE_ADMIN')) {
            $reportService = $this->get('food.report');
            $dateFrom = new \DateTime("-1 week");
            $dateTo = new \DateTime("now");

            $orderCountGraph = $reportService->prepareOrderCountByDayGraph($dateFrom, $dateTo);
            $smsCountGraph = $reportService->prepareSmsCountByDayGraph($dateFrom, $dateTo);

            return $this->render(
                'FoodReportBundle:Report:dasboard_stats.html.twig',
                array(
                    'orderCountGraph' => $orderCountGraph,
                    'smsCountGraph' => $smsCountGraph,
                )
            );
        }

        return new Response();
    }
}
