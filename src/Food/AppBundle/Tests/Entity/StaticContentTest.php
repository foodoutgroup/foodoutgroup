<?php

namespace Food\AppBundle\Tests\Entity;

use Food\AppBundle\Entity\StaticContent;

class AdminTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters()
    {
        $entityId = 5;
        $entityTitle = 'OmgTitle';
        $entityContent = 'Wow so content :)';

        $staticEntity = new StaticContent();

        $stringTest1 = $staticEntity->__toString();
        $this->assertEquals('', $stringTest1);

        $staticEntity->setTitle($entityTitle);
        $titleTest = $staticEntity->getTitle();
        $this->assertEquals($entityTitle, $titleTest);

        $staticEntity->setContent($entityContent);
        $testContent = $staticEntity->getContent();
        $this->assertEquals($entityContent, $testContent);
    }
}
