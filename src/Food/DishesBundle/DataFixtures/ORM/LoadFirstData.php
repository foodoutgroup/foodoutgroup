<?php

namespace Food\DishesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Food\UserBundle\Entity\User;

class LoadFirstData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $admin = new User() ;
        $admin->setEmail("admin@skanu.lt") ;
        $admin->setUsername("admin") ;
        $admin->setPlainPassword("admin") ;
        $admin->setEnabled(true) ;
        $admin->setRoles( array('ROLE_ADMIN') ) ;
        $admin->setSuperAdmin(true) ;

        $manager->persist($admin);
        $manager->flush();


        $user1 = new User() ;
        $user1->setEmail("moderator@skanu.lt") ;
        $user1->setUsername("moderator") ;
        $user1->setPlainPassword("moderator") ;
        $user1->setEnabled(true) ;
        $user1->setRoles( array('ROLE_MODERATOR') ) ;

    }
}