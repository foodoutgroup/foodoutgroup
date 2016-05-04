<?php

namespace Food\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Food\OrderBundle\Service\OrderService;

/**
 * UserRepository
 */
class UserRepository extends EntityRepository
{
    private $_discount = null;

    public function getDiscount(User $user)
    {
        if (is_null($this->_discount)) {
            if ($user->getDiscount() > 0) {
                $this->_discount = $user->getDiscount();
            } else {
                $query = 'SELECT dl.discount 
                        FROM (
                          SELECT sum(o.total) total 
                          FROM orders o 
                          WHERE o.order_date >= :date 
                            AND o.order_status = :status 
                            AND o.user_id = :userId) as o 
                        LEFT JOIN discount_level dl 
                          ON o.total BETWEEN dl.range_start AND dl.range_end 
                            OR dl.range_start IS NULL AND (o.total < dl.range_end or o.total IS NULL) 
                            OR dl.range_end IS NULL AND o.total > dl.range_start';

                $stmt = $this->getEntityManager()
                    ->getConnection()
                    ->prepare($query);

                $stmt->bindValue("date", date('Y-m-01 00:00:00'));
                $stmt->bindValue("status", OrderService::$status_completed);
                $stmt->bindValue("userId", $user->getId());

                $stmt->execute();
                $discount = $stmt->fetchColumn();

                if (!$discount) {
                    $discount = 0;
                }

                $this->_discount = $discount;
            }
        }

        return $this->_discount;
    }
}
