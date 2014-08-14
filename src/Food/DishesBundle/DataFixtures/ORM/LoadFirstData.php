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

        /* Initiate Static content */
        $staticFaq = new StaticContent();
        $staticFaq->setTitle('D.U.K')
            ->setContent('Dažniausiai užduodami klausimai. Gerai pagalvok, ko klausi!')
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;
        $manager->persist($staticFaq);
        $manager->flush();

        // Save static translations
        $staticEnTitle = new StaticContentLocalized('en', 'title', 'FAQ');
        $staticEnTitle->setObject($staticFaq);

        $staticRuTitle = new StaticContentLocalized('ru', 'title', 'ЧЗВ');
        $staticRuTitle->setObject($staticFaq);

        $staticEnContent = new StaticContentLocalized('en', 'content', 'Frequently asked questions. Think what are You asking!');
        $staticEnContent->setObject($staticFaq);

        $staticRuContent = new StaticContentLocalized('ru', 'content', 'часто задаваемые вопросы. Думайте, какие вопросы спрашиваете!');
        $staticRuContent->setObject($staticFaq);

        $staticFaq->addTranslation($staticEnTitle);
        $staticFaq->addTranslation($staticRuTitle);
        $staticFaq->addTranslation($staticEnContent);
        $staticFaq->addTranslation($staticRuContent);
        $manager->persist($staticFaq);
        $manager->flush();

        // Create static page for contacts
        $staticContact = new StaticContent();
        $staticContact->setTitle('Kontaktai')
            ->setContent('Pagalbos telefonas: 3706666666666, Administracija: 36000000000')
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($staticContact);
        $manager->flush();

        // Save static translations
        $staticEnTitle = new StaticContentLocalized('en', 'title', 'Contact');
        $staticEnTitle->setObject($staticContact);

        $staticRuTitle = new StaticContentLocalized('ru', 'title', 'Cвязи');
        $staticRuTitle->setObject($staticContact);

        $staticEnContent = new StaticContentLocalized('en', 'content', 'Support phone: 3706666666666, Administration: 3706000000000');
        $staticEnContent->setObject($staticContact);

        $staticRuContent = new StaticContentLocalized('ru', 'content', 'Номер телефона экстренной связи: 3706666666666, Администрация: 36000000000');
        $staticRuContent->setObject($staticContact);

        $staticContact->addTranslation($staticEnTitle);
        $staticContact ->addTranslation($staticRuTitle);
        $staticContact->addTranslation($staticEnContent);
        $staticContact->addTranslation($staticRuContent);
        $manager->persist($staticContact);
        $manager->flush();

        // Create static page for rules
        $rulesContent = new StaticContent();
        $rulesContent->setTitle('ordering_rules')
            ->setContent('taisykle numeris 1 - nera taisykliu')
            ->setCreatedAt(new \DateTime('now'))
            ->setCreatedBy($admin)
        ;

        $manager->persist($rulesContent);
        $manager->flush();

        // Save static translations
        $staticEnTitle = new StaticContentLocalized('en', 'title', 'ordering_rules');
        $staticEnTitle->setObject($rulesContent);

        $staticRuTitle = new StaticContentLocalized('ru', 'title', 'ordering_rules');
        $staticRuTitle->setObject($rulesContent);

        $staticEnContent = new StaticContentLocalized('en', 'content', 'taisykle numeris 1 - nera taisykliu');
        $staticEnContent->setObject($rulesContent);

        $staticRuContent = new StaticContentLocalized('ru', 'content', 'taisykle numeris 1 - nera taisykliu');
        $staticRuContent->setObject($rulesContent);

        $rulesContent->addTranslation($staticEnTitle);
        $rulesContent->addTranslation($staticRuTitle);
        $rulesContent->addTranslation($staticEnContent);
        $rulesContent->addTranslation($staticRuContent);
        $manager->persist($rulesContent);
        $manager->flush();
    }

    public function getOrder()
    {
        return 2;
    }
}