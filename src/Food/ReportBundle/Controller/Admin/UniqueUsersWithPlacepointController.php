<?php

namespace Food\ReportBundle\Controller\Admin;

use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;

class UniqueUsersWithPlacepointController extends CRUDController
{

    public function listAction()
    {

        $request = $this->getRequest();

        $resultCollection = null;
        if($request->get('from', null)) {

            $resultCollection = $this->getReportResult($request->get('from', null));
        }

        return $this->render('@FoodReport/Admin/UniqueUsersWithPlacepoint/list.html.twig', array(
            'resultCollection' => $resultCollection,
            'val' => $request->get('from', null),
            'base_template' => $this->getBaseTemplate(),
            'admin_pool'    => $this->container->get('sonata.admin.pool'),
            'blocks'        => $this->container->getParameter('sonata.admin.configuration.dashboard_blocks')
        ));
    }

    private function getReportResult($from = null)
    {

        if($from == null) {
            $from = "2015-01-01";
        }

        $from = $from . " 00:00:00";

        $con = $this->container->get('doctrine')->getConnection();
        $activeMembers1sql = "SELECT DISTINCT SUBSTRING(order_date, 1, 7) as date, COUNT(DISTINCT user_id) as count FROM orders WHERE order_date > '$from' GROUP BY 1";
        var_dump($activeMembers1sql);

        $stmt = $con->prepare($activeMembers1sql);
        $stmt->execute();
        $activeMembers1 = $stmt->fetchAll();
        $activeMembersData1 = array();
        foreach ($activeMembers1 as $row) {
            $activeMembersData1[$row['date']] = $row['count'];
        }

        $activeMembers2sql = "SELECT DISTINCT SUBSTRING(order_date, 1, 7) as date, user_id, COUNT(*) as cnt FROM orders WHERE order_date > '$from' GROUP BY 1,2 HAVING COUNT(*) > 1";
        $stmt = $con->prepare($activeMembers2sql);
        $stmt->execute();
        $activeMembers2 = $stmt->fetchAll();
        $activeMembersData2 = array();
        foreach ($activeMembers2 as $row) {
            if (empty($activeMembersData2[$row['date']])) {
                $activeMembersData2[$row['date']] = 0;
            }
            $activeMembersData2[$row['date']]++;
        }

        $newPlacePointsSql = "SELECT DISTINCT SUBSTRING(created_at, 1, 7) as date, COUNT(*) as count FROM place_point WHERE created_at > '$from' GROUP BY 1";
        $stmt = $con->prepare($newPlacePointsSql);
        $stmt->execute();
        $newPlacePoints = $stmt->fetchAll();
        $newPlacePointsData = array();
        foreach ($newPlacePoints as $row) {
            $newPlacePointsData[$row['date']] = $row['count'];
        }

        return [
            'activeMembers1' => $activeMembersData1,
            'activeMembers2' => $activeMembersData2,
            'newPlacePoints' => $newPlacePointsData,
            'keys' => array_keys($activeMembersData1)
        ];

    }
}