<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

class OrderExtraRepository extends EntityRepository
{
    /**
     * @param $phone
     *
     * @return OrderExtra[]
     */
    public function getUserByPhone($phone)
    {
        $result = $this->getEntityManager()
            ->createQuery(
                'SELECT oe FROM FoodOrderBundle:OrderExtra oe WHERE oe.phone LIKE :phone ORDER BY oe.id DESC'
            )
            ->setParameter('phone', '%' . $phone)
            ->getResult()
        ;

        return $result;
    }
}