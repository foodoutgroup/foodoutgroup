<?php

namespace Food\ReportBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\AdminBundle\Datagrid\Datagrid;

class DriverWorktimeAdminController extends Controller
{

    public function listAction()
    {

        $worktimes = $this->get('food.driver_service')->calculateDriversWorktimesLastMonth();

        return $this->render(
            'FoodReportBundle:Report:driver_worktime_report.html.twig',
            array(
                'worktimes' => $worktimes
            )
        );

        //$info = $this->get('food.driver_service')->getDriverWorkhours(12);
        //var_dump($info);die;
        $datagrid = $this->admin->getDatagrid();
        $formView = $datagrid->getForm()->createView();

        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'     => 'list',
            'form'       => $formView,
            'datagrid'   => $datagrid,
            'csrf_token' => $this->getCsrfToken('sonata.batch'),
        ));
    }
}
