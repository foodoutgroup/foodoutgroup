<?php

namespace Food\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Entity\StaticContent;
use Food\AppBundle\Entity\StaticContentLocalized;

class EstoniaDataFixtures implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // Create static page for rules
        $rulesContent = new StaticContent();
        $rulesContent->setTitle('ordering_rules')
            ->setContent('taisykle numeris 1 - nera taisykliu')
            ->setCreatedAt(new \DateTime('now'))
        ;

        $manager->persist($rulesContent);
        $manager->flush();

        // Save static translations
        $staticEnTitle = new StaticContentLocalized('en', 'title', 'ordering_rules');
        $staticEnTitle->setObject($rulesContent);

        $staticRuTitle = new StaticContentLocalized('ee', 'title', 'ordering_rules');
        $staticRuTitle->setObject($rulesContent);

        $staticEnContent = new StaticContentLocalized('en', 'content', 'taisykle numeris 1 - nera taisykliu');
        $staticEnContent->setObject($rulesContent);

        $staticRuContent = new StaticContentLocalized('ee', 'content', 'taisykle numeris 1 - nera taisykliu');
        $staticRuContent->setObject($rulesContent);

        $rulesContent->addTranslation($staticEnTitle);
        $rulesContent->addTranslation($staticRuTitle);
        $rulesContent->addTranslation($staticEnContent);
        $rulesContent->addTranslation($staticRuContent);
        $manager->persist($rulesContent);
        $manager->flush();


        $slug = new Slug();
        $slug->setType('text');
        $slug->setItemId($rulesContent->getId());
        $slug->setLangId('ee');
        $slug->setName('ordering_rules');
        $slug->setOrigName('ordering_rules');
        $slug->setActive(true);
        $manager->persist($slug);
        $manager->flush();
    }
}