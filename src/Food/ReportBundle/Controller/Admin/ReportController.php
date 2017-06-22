<?php

namespace Food\ReportBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class ReportController extends CRUDController
{

    public function listAction()
    {

        $reportCollection = [];
        $reportCollection[] = $this->get('sonata.admin.restaurant_orders');
        $reportCollection[] = $this->get('sonata.admin.restaurant_orders_report');
        $reportCollection[] = $this->get('sonata.admin.driver_worktime');
        $reportCollection[] = $this->get('sonata.admin.unique_users_with_placepoints');
        $reportCollection[] = $this->get('sonata.admin.report.orders_by_restaurant');

        return $this->render('@FoodReport/Admin/Report/list.html.twig', array(
            'reportCollection' => $reportCollection,
            'base_template' => $this->getBaseTemplate(),
            't' => $this->get('translator'),
            'admin_pool'    => $this->container->get('sonata.admin.pool'),
            'blocks'        => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }

}