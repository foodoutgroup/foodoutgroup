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
}
