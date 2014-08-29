<?php
namespace Food\ReportBundle\Service;

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

        $orderData = $this->getDoctrine()->getRepository('FoodOrderBundle:Order')
            ->getOrderCountByDay($dateFrom, $dateTo);

        $orderGraphData = $this->remapDataForGraph($orderData, 'report_day', 'order_count');
        $series = array(
            array(
                "name" => $translator->trans('admin.report.orders'),
                "data" => array_values($orderGraphData),
                'type' => 'spline',
            )
        );

        $ob = new Highchart();
        $ob->chart->renderTo('order_chart');
        $ob->title->text($translator->trans('admin.report.daily_orders_graph'));
        $ob->yAxis->title(array('text'  => $translator->trans('admin.report.amount')));
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

        $smsGraphData = $this->remapDataForGraph($smsData, 'report_day', 'message_count');
        $series = array(
            array(
                "name" => $translator->trans('admin.report.sms'),
                "data" => array_values($smsGraphData),
                'type' => 'spline',
            )
        );

        $ob = new Highchart();
        $ob->chart->renderTo('sms_chart');
        $ob->title->text($translator->trans('admin.report.daily_sms_graph'));
        $ob->yAxis->title(array('text'  => $translator->trans('admin.report.amount')));
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
}