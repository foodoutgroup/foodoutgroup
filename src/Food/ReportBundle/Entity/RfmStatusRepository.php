<?php

namespace Food\ReportBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 */
class RfmStatusRepository extends EntityRepository
{
    public function getStatusByRfm($rfm)
    {
        $query = 'SELECT `title`
                    FROM `rfm_status`
                    WHERE :rfm BETWEEN `rfm_from` AND `rfm_to`
                        OR `rfm_from` IS NULL AND (:rfm < `rfm_to` or :rfm IS NULL) 
                        OR `rfm_to` IS NULL AND :rfm > `rfm_from`
                    LIMIT 1';
        $stmt = $this->getEntityManager()
            ->getConnection()
            ->prepare($query)
        ;

        $stmt->bindValue("rfm", $rfm);

        $stmt->execute();

        $result = $stmt->fetchColumn();

        return $result;
    }
}
