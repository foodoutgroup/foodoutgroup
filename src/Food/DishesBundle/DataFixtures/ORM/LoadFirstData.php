<?php

namespace Food\DishesBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Food\AppBundle\Entity\StaticContent;
use Food\AppBundle\Entity\StaticContentLocalized;
use Food\DishesBundle\Entity\Kitchen;
use Food\DishesBundle\Entity\Place;
use Food\UserBundle\Entity\User;
use Food\DishesBundle\Entity\Kithcen;
use Symfony\Component\Validator\Constraints\DateTime;

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


        $kitchen1 = new Kitchen();
        $kitchen1->setName('Picos')
            ->setVisible(true)
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
            ;

        $manager->persist($kitchen1);
        $manager->flush();

        $kitchen2 = new Kitchen();
        $kitchen2->setName('Itališka')
            ->setVisible(true)
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($kitchen2);
        $manager->flush();

        $kitchen3 = new Kitchen();
        $kitchen3->setName('Japoniška')
            ->setVisible(true)
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($kitchen3);
        $manager->flush();

        $kitchen4 = new Kitchen();
        $kitchen4->setName('Kiniška')
            ->setVisible(true)
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;
        $manager->persist($kitchen4);
        $manager->flush();


        $user1 = new User() ;
        $user1->setEmail("moderator@skanu.lt") ;
        $user1->setUsername("moderator") ;
        $user1->setPlainPassword("moderator") ;
        $user1->setEnabled(true) ;
        $user1->setRoles( array('ROLE_MODERATOR') ) ;

        $manager->persist($user1);
        $manager->flush();

        /* Initiate Static content */
        $staticFaq = new StaticContent();
        $staticFaq->setTitle('D.U.K')
            ->setContent('Dažniausiai užduodami klausimai. Gerai pagalvok, ko klausi!')
            ->addTranslation(new StaticContentLocalized('en', 'title', 'FAQ'))
            ->addTranslation(new StaticContentLocalized('ru', 'title', 'ЧЗВ'))
            ->addTranslation(new StaticContentLocalized('en', 'content', 'Frequently asked questions. Think what are You asking!'))
            ->addTranslation(new StaticContentLocalized('ru', 'content', 'часто задаваемые вопросы. Думайте, какие вопросы спрашиваете!'))
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($staticFaq);
        $manager->flush();

        $staticFaq = new StaticContent();
        $staticFaq->setTitle('Kontaktai')
            ->setContent('Pagalbos telefonas: 3706666666666, Administracija: 36000000000')
            ->addTranslation(new StaticContentLocalized('en', 'title', 'Contact'))
            ->addTranslation(new StaticContentLocalized('ru', 'title', 'Cвязи'))
            ->addTranslation(new StaticContentLocalized('en', 'content', 'Support phone: 3706666666666, Administration: 3706000000000'))
            ->addTranslation(new StaticContentLocalized('ru', 'content', 'Номер телефона экстренной связи: 3706666666666, Администрация: 36000000000'))
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($staticFaq);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}