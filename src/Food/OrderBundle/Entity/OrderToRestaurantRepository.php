<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class OrderToRestaurantRepository extends EntityRepository
{
    /**
     * @return OrderToDriver[]
     */
    public function getOrdersToSend()
    {
        return $this->createQueryBuilder('or')
            ->andWhere('or.dateAdded >= :date')
            ->andWhere('or.dateSent IS NULL')
            ->setParameters([
                    'date' => new \DateTime('-1 week')
            ])
            ->getQuery()
            ->getResult();
    }
}
