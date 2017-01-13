<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAware;

class DishSizeRepository extends EntityRepository
{
    public function findDishSizeByCodeAndPlace($code, Place $place) {

        $querty = "SELECT ds.id FROM dish_size ds 
                  INNER JOIN dish d ON ds.dish_id = d.id 
                  WHERE ds.code = '".$code."' AND d.place_id = " . $place->getId();

        $stmt = $this->getEntityManager()->getConnection()->prepare($querty);
        $stmt->execute();
        if($stmt->rowCount()) {
            $dishId = $stmt->fetchColumn(0);
            return $this->findOneBy(['id' => $dishId]);
        }

        return false;
    }
}
