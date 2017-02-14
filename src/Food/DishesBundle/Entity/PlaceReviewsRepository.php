<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\EntityRepository;

class PlaceReviewsRepository extends EntityRepository
{
    public function getActiveReviewsByPlace(Place $place)
    {
        return $this->findBy(['place' => $place, 'active' => true]);
    }
}