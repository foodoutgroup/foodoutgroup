<?php
namespace Food\ReportBundle\Service;

use Food\OrderBundle\Service\OrderService;
use Ob\HighchartsBundle\Highcharts\Highchart;
use Symfony\Component\DependencyInjection\ContainerAware;

class ReportService extends ContainerAware
{
    /**
     * @return \Doctrine\Bundle\DoctrineBundle\Registry
     */
    private function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return Highchart
     * @throws \InvalidArgumentException
     */
    public function prepareOrderCountByDayGraph($dateFrom, $dateTo)
    {
        if (empty($dateFrom) || empty($dateTo)) {
            throw new \InvalidArgumentException('You must specify dates from and to in order to get graph');
        }

        $translator = $this->container->get('translator');

        $orderRepo = $this->getDoctrine()->getRepository('FoodOrderBundle:Order');

        $orderData = $orderRepo->getOrderCountByDay($dateFrom, $dateTo);
        $orderCanceledData = $orderRepo->getOrderCountByDay($dateFrom, $dateTo, OrderService::$status_canceled);
        $orderMobileData = $orderRepo->getOrderCountByDay($dateFrom, $dateTo, null, true);

        $orderGraphData = $this->fillEmptyDays(
            $this->remapDataForGraph($orderData, 'report_day', 'order_count'),
            $dateFrom,
            $dateTo
        );
        $orderCancelGraphData = $this->fillEmptyDays(
            $this->remapDataForGraph($orderCanceledData, 'report_day', 'order_count'),
            $dateFrom,
            $dateTo
        );

        $orderMobileCountGraphData = $this->fillEmptyDays(
            $this->remapDataForGraph($orderMobileData, 'report_day', 'order_count'),
            $dateFrom,
            $dateTo
        );

        $series = array(
            array(
                "name" => $translator->trans('admin.report.orders'),
                "data" => array_values($orderGraphData),
                'type' => 'spline',
            ),
            array(
                "name" => $translator->trans('admin.report.orders_canceled'),
                "data" => array_values($orderCancelGraphData),
                'type' => 'spline',
            ),
            array(
                'name' => $translator->trans('admin.report.mobile_orders'),
                'data' => array_values($orderMobileCountGraphData),
                'type' => 'spline'
            )
        );

        $ob = new Highchart();
        $ob->chart->renderTo('order_chart');
        $ob->title->text($translator->trans('admin.report.daily_orders_graph'));
        $ob->yAxis->title(array('text'  => $translator->trans('admin.report.amount')));
        $ob->yAxis->floor(0);
        $ob->xAxis->categories(array_keys($orderGraphData));
        $ob->series($series);

        return $ob;
    }

    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return Highchart
     * @throws \InvalidArgumentException
     */
    public function prepareSmsCountByDayGraph($dateFrom, $dateTo)
    {
        if (empty($dateFrom) || empty($dateTo)) {
            throw new \InvalidArgumentException('You must specify dates from and to in order to get graph');
        }

        $translator = $this->container->get('translator');

        $smsData = $this->getDoctrine()->getRepository('FoodSmsBundle:Message')
            ->getSmsCountByDay($dateFrom, $dateTo);

        $smsUndeliveredData = $this->getDoctrine()->getRepository('FoodSmsBundle:Message')
            ->getSmsUndeliveredCountByDay($dateFrom, $dateTo);

        $smsGraphData = $this->fillEmptyDays(
                $this->remapDataForGraph($smsData, 'report_day', 'message_count'),
                $dateFrom,
                $dateTo
            );

        $smsUndeliveredGraphData = $this->fillEmptyDays(
                $this->remapDataForGraph($smsUndeliveredData, 'report_day', 'message_count'),
                $dateFrom,
                $dateTo
            );

        $series = array(
            array(
                "name" => $translator->trans('admin.report.sms'),
                "data" => array_values($smsGraphData),
                'type' => 'spline',
            ),
            array(
                "name" => $translator->trans('admin.report.sms_undelivered'),
                "data" => array_values($smsUndeliveredGraphData),
                'type' => 'spline',
            )
        );

        $ob = new Highchart();
        $ob->chart->renderTo('sms_chart');
        $ob->title->text($translator->trans('admin.report.daily_sms_graph'));
        $ob->yAxis->title(array('text'  => $translator->trans('admin.report.amount')));
        $ob->yAxis->floor(0);
        $ob->xAxis->categories(array_keys($smsGraphData));
        $ob->series($series);

        return $ob;
    }

    /**
     * @param array $data
     * @param string $keyColumn
     * @param string $valueColumn
     * @return array
     */
    protected function remapDataForGraph($data, $keyColumn, $valueColumn)
    {
        $remapedData = array();

        foreach ($data as $row) {
            $remapedData[$row[$keyColumn]] = (int)$row[$valueColumn];
        }

        return $remapedData;
    }

    /**
     * @param array $data
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     *
     * @return array
     */
    protected function fillEmptyDays($data, $dateFrom, $dateTo)
    {
        $currentDate = clone $dateFrom;

        while($currentDate->format("y-m-d") < $dateTo->format("y-m-d"))
        {
            $formatedDate = $currentDate->format("y-m-d");
            if (!isset($data[$formatedDate])) {
                $data[$formatedDate] = 0;
            }

            $currentDate->add(new \DateInterval('P1D'));
        }

        ksort($data);
        return $data;
    }
}