<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;

class DriverWorktimeAdminController extends Controller
{
    public function listAction()
    {
        $stats = $this->get('food.report')->calculateDriverLatencyLastMonth();

        return $this->render(
            'FoodReportBundle:Report:driver_worktime_report.html.twig',
            array(
                'stats' => $stats
            )
        );
    }
}
