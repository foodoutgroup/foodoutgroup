<?php

namespace Food\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserAddressRepository extends EntityRepository
{

    public function findByIdUserFlat($hash, User $user, $flat = null)
    {
        $params = ['addressId' => $hash, 'user' => $user, 'flat' => $flat];
        if($flat == null){
            unset($params['flat']);
        }
        return $this->findOneBy($params);
    }

    public function getDefault(User $user)
    {
        $qb = $this->createQueryBuilder('ua')
            ->where('ua.user = :user')
            ->andWhere('ua.default = 1')
            ->orderBy('ua.id', 'DESC');

        $result = $qb->getQuery()->execute(['user' => $user->getId()]);

        return count($result) ? $result[0] : null;
    }

}
