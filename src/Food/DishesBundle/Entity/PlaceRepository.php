<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;

class PlaceRepository extends EntityRepository
{
    public function findByKitchensIds($kitchens)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.kitchens', 'f')
            ->where($qb->expr()->in('f.id', $kitchens));
        return $qb->getQuery()->getResult();
    }
}

?>