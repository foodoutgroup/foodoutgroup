<?php

namespace Food\AppBundle\Entity;
use Doctrine\ORM\EntityRepository;

class DriverRepository extends EntityRepository
{
    /**
     * Gets driver data even if it is deleted
     * @param $id
     * @return mixed
     */
    public function getDriverPxIfDeleted($id)
    {

        $query = '
        SELECT d.*
        FROM `orders` o
        LEFT JOIN `drivers` d ON d.id = o.`driver_id`
        WHERE
        o.id = '.$id.'
        LIMIT 1
        ';

        $stmt = $this->getEntityManager()->getConnection()
            ->prepare($query);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findRecentlyDeleted()
    {
        $query = '
        SELECT d.*
        FROM `drivers` d
        WHERE
          d.deleted_at >= "'.date("Y-m-d 00:00:01", strtotime('-3 month')).'"
        ';

        $stmt = $this->getEntityManager()->getConnection()
            ->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getLastMonthLatency()
    {
        $dateStart = date('Y-m-01', strtotime('-1 month'));
        $dateEnd = date('Y-m-t', strtotime('-1 month'));

        $query = "
        SELECT
            o.driver_id,
            d.name,
            TIME_TO_SEC(
              TIMEDIFF(
                (
                    SELECT osl.event_date
                    FROM order_status_log osl
                    WHERE
                        osl.order_id = o.id
                        AND osl.new_status = 'completed'
                    LIMIT 1
                ),
                o.delivery_time
            )) AS 'time_difference_seconds'
        FROM `orders` o
        LEFT JOIN drivers d ON d.id = o.driver_id
        WHERE
            o.order_date BETWEEN '{$dateStart}' AND '{$dateEnd}'
            AND o.delivery_type = 'deliver'
            AND o.order_status IN ('completed', 'partialy_completed')
            AND o.driver_id IS NOT NULL
        ORDER BY o.driver_id ASC
        ";

        $stmt = $this->getEntityManager()->getConnection()
            ->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDriverWorkedTime($driverId, $fromDate = null, $toDate = null)
    {
        $time = 0;

        if(!$fromDate) {
            $fromDate = date('Y-m-d');
            $toDate = $fromDate;
        }
        if (!$toDate) {
            $toDate = $fromDate;
        }
        // fromDate nuo 6 ryto
        // toDate iki kitos dienos 6 ryto
        $query = "
SELECT
  TIME_TO_SEC(TIMEDIFF(MAX(event_date), MIN(event_date))) AS timeDiff
FROM order_delivery_log
LEFT JOIN orders ON order_id = orders.id
WHERE driver_id IS NOT NULL
      AND order_date BETWEEN DATE('{$fromDate}') + INTERVAL 6 HOUR AND DATE('{$toDate}') + INTERVAL 30 HOUR
      AND delivery_type = 'deliver'
      AND preorder != '1'
      AND event IN('order_assigned', 'order_completed')
      AND driver_id = '{$driverId}'
  GROUP BY DATE(order_date)
        ";

        $stmt = $this->getEntityManager()->getConnection()
            ->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll();
        if (!empty($results)) {
            foreach ($results as $result) {
                $time += $result['timeDiff'];
            }
        }
        $time = number_format($time/3600, 2, '.', '');
        return $time;
    }
}
