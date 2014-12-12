<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;

class DishRepository extends EntityRepository
{

    public function getCategoryActiveDishes($categoryId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.categories', 'c')
            ->where('c.id = '.$categoryId)
            ->andWhere('d.active = 1');

        return $qb->getQuery()->getResult();
    }

    public function getSmallestPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.price')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.price', 'DESC')
            ;
        return $qb->getQuery()->getResult();
    }

    public function getLargestPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.price')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.price', 'ASC')
            ;
        return $qb->getQuery()->getResult();
    }

    public function getSmallestDiscountPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.discountPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.discountPrice', 'DESC')
        ;

        return $qb->getQuery()->getResult();
    }

    public function getLargestDiscountPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.discountPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.discountPrice', 'ASC')
        ;

        return $qb->getQuery()->getResult();
    }


    public function hasDiscountPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.price, s.discountPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->andWhere('s.discountPrice > 0')
            ->orderBy('s.discountPrice', 'DESC')
        ;

        if ($qb->getQuery()->getResult()) {
            return true;
        } else {
            return false;
        }
    }
}

?>