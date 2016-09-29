<?php

namespace Food\ReportBundle\Controller;

use Exporter\Source\ArraySourceIterator;
use Exporter\Source\ChainSourceIterator;
use Exporter\Source\CsvSourceIterator;
use Exporter\Source\DoctrineORMQuerySourceIterator;
use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DriverWorktimeAdminController extends Controller
{

    public function listAction()
    {
        $request = $this->get('request');
        $dateFrom = new \DateTime($request->get('date_from', 'first day of this month'));
        $dateTo = new \DateTime($request->get('date_to', 'now'));

        $worktimes = $this->get('food.driver_service')->calculateDriversWorktimes($dateFrom, $dateTo);

        array_walk($worktimes, function(&$worktime) {
            array_shift($worktime);
        });

        return $this->render(
            'FoodReportBundle:Report:driver_worktime_report.html.twig',
            array(
                'worktimes' => $worktimes,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            )
        );
    }

    /**
     * @param Request $request
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     *
     * @return Response
     */
    public function exportAction(Request $request)
    {
        if (false === $this->admin->isGranted('EXPORT')) {
            throw new AccessDeniedException();
        }

        $translator = $this->get('translator');
        $format = $request->get('format');

        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $dateFrom = new \DateTime($dateFrom['date']);
        $dateTo = new \DateTime($dateTo['date']);

        $filename = sprintf('export_%s_%s.%s',
            strtolower($translator->trans('drivers_worktimes')),
            $dateFrom->format('Y_m_d') . '_' . $dateTo->format('Y_m_d'),
            $format
        );

        $worktimes = $this->get('food.driver_service')->calculateDriversWorktimes($dateFrom, $dateTo);
        $sourceIterator = new ArraySourceIterator($worktimes);

        return $this->get('sonata.admin.exporter')->getResponse($format, $filename, $sourceIterator);
    }

}
