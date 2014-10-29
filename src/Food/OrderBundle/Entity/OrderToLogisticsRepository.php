<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class OrderToLogisticsRepository extends EntityRepository
{
    /**
     * @return OrderToLogistics[]
     */
    public function getOrdersToSend()
    {
        return $this->createQueryBuilder('ol')
            ->where('ol.status = :status')
            ->andWhere('ol.dateAdded >= :date')
            ->setParameters(
                array(
                    'status' => 'unsent',
                    'date' => new \DateTime('- 1 week')
                )
            )
            ->getQuery()
            ->getResult();
    }
}