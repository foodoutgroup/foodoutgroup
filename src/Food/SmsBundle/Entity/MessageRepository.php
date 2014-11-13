<?php

namespace Food\SmsBundle\Entity;
use Doctrine\ORM\EntityRepository;

class MessageRepository extends EntityRepository
{
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getSmsCountByDay($dateFrom, $dateTo)
    {
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 00:00:01");

        $query = sprintf(
            $this->getReportQuery(),
            "s.sent = 1
            AND s.delivered = 1
            AND (s.submitted_at BETWEEN '{$dateFrom}' AND '{$dateTo}')"
        );

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    /**
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return array
     */
    public function getSmsUndeliveredCountByDay($dateFrom, $dateTo)
    {
        $dateFrom = $dateFrom->format("Y-m-d 00:00:01");
        $dateTo = $dateTo->format("Y-m-d 00:00:01");

        $query = sprintf(
            $this->getReportQuery(),
            "s.sent = 1
            AND s.received_at IS NULL
            AND (s.submitted_at BETWEEN '{$dateFrom}' AND '{$dateTo}')"
        );

        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return string
     */
    private function getReportQuery()
    {
        return "
          SELECT
            DATE_FORMAT(s.submitted_at, '%%m-%%d') AS report_day,
            COUNT(s.id) AS message_count
          FROM sms_message s
          WHERE
            %s
          GROUP BY DATE_FORMAT(s.submitted_at, '%%m-%%d')
          ORDER BY DATE_FORMAT(s.submitted_at, '%%m-%%d') ASC
        ";
    }
}