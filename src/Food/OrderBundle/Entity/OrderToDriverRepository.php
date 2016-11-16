<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class OrderToDriverRepository extends EntityRepository
{
    /**
     * @return OrderToDriver[]
     */
    public function getOrdersToSend()
    {
        return $this->createQueryBuilder('od')
            ->andWhere('od.dateAdded >= :date')
            ->andWhere('od.dateSent IS NULL')
            ->setParameters([
                    'date' => new \DateTime('-1 week')
            ])
            ->getQuery()
            ->getResult();
    }
}
