<?php

namespace Food\AppBundle\Entity;
use Doctrine\ORM\EntityRepository;

class UnusedSfNumbersRepository extends EntityRepository
{
    /**
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOldest()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('sf')
            ->from('\Food\AppBundle\Entity\UnusedSfNumbers', 'sf')
            ->orderBy('sf.sfNumber', 'ASC')
            ->setMaxResults(1);

        $result = $qb->getQuery()->getResult();

        if (empty($result) || count($result) < 1) {
            return null;
        } else {
            return $result[0];
        }
    }
}