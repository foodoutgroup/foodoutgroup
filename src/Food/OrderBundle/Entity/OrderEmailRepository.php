<?php

namespace Food\OrderBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * OrderEmailRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class OrderEmailRepository extends EntityRepository
{
    public function getEmailsToSend(){
      return $this->findBy(['sent'=>0]);
    }
}
