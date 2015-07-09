<?php

namespace Food\DishesBundle\Entity;
use Doctrine\ORM\EntityRepository;

class DishRepository extends EntityRepository
{

    public function getCategoryActiveDishes($categoryId)
    {
        $currentWeek = date('W') % 2 == 1; # 1 - odd 0 - even
        $currentWeek = $currentWeek ? 0 : 1;

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.categories', 'c')
            ->leftJoin('d.dates', 'dd', 'WITH', 'dd.dish = d.id')
            ->where('c.id = '.$categoryId)
            ->andWhere('d.active = 1
                AND ((d.useDateInterval = 1 AND :curr_date >= dd.start AND :curr_date <= dd.end) OR (d.useDateInterval = 0)
                AND (d.checkEvenOddWeek = 1 AND d.evenWeek = :curr_week) OR (d.checkEvenOddWeek = 0))
            ')
            ->setParameter('curr_date', date('Y-m-d'))
            ->setParameter('curr_week', $currentWeek);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $dishId
     * @return array
     */
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

    /**
     * @param int $dishId
     * @return array
     */
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

    /**
     * @param int $dishId
     * @return array
     */
    public function getSmallestPublicPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.publicPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.publicPrice', 'DESC')
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @param int $dishId
     * @return array
     */
    public function getLargestPublicPrice($dishId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.publicPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.publicPrice', 'ASC')
        ;
        return $qb->getQuery()->getResult();
    }


    /**
     * @param int $dishId
     * @return array
     */
    public function getSmallestDiscountPrice($dishId)
    {
        /*
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.discountPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.discountPrice', 'DESC')
        ;

        return $qb->getQuery()->getResult();
        */
        $query = "SELECT IF(discount_price < 1 OR discount_price IS NULL OR discount_price ='' , price, discount_price) as discountPrice FROM dish_size WHERE deleted_at IS NULL AND dish_id=".intval($dishId)." ORDER BY IF(discount_price < 1 OR discount_price IS NULL OR discount_price ='', price, discount_price) ASC";
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param int $dishId
     * @return array
     */
    public function getLargestDiscountPrice($dishId)
    {
        /*
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('d, s.discountPrice')
            ->from('\Food\DishesBundle\Entity\Dish', 'd')
            ->join('d.sizes', 's')
            ->where('d.id = '.$dishId)
            ->andWhere('d.active = 1')
            ->orderBy('s.discountPrice', 'ASC')
        ;

        return $qb->getQuery()->getResult();
        */
        $query = "SELECT IF(discount_price < 1 OR discount_price IS NULL OR discount_price ='' , price, discount_price) as discountPrice FROM dish_size WHERE deleted_at IS NULL AND dish_id=".intval($dishId)." ORDER BY IF(discount_price < 1 OR discount_price IS NULL OR discount_price ='', price, discount_price) DESC";
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCountOfDiscountSizes($dishId)
    {
        $query = "SELECT COUNT(*) as cnt FROM dish_size WHERE deleted_at IS NULL AND discount_price > 0 AND dish_id=".intval($dishId);
        $stmt = $this->getEntityManager()->getConnection()->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param int $dishId
     * @return bool
     */
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