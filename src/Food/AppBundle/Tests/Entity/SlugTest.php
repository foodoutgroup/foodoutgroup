<?php

namespace Food\AppBundle\Tests\Entity;

use Food\AppBundle\Entity\Slug;

class SlugTest extends \PHPUnit_Framework_TestCase
{
    public function testActiveNotActive()
    {
        $slugEntity = new Slug();

        $slugEntity->setActive(true);
        $this->assertTrue($slugEntity->isActive());

        $slugEntity->setActive(false);
        $this->assertFalse($slugEntity->isActive());
        $this->assertFalse($slugEntity->getActive());
    }

    public function testItemId()
    {
        $itemId = 114;

        $slugEntity = new Slug();

        $slugEntity->setItemId($itemId);
        $this->assertEquals($itemId, $slugEntity->getItemId());
    }

    public function testLangId()
    {
        $langId = 'lt';

        $slugEntity = new Slug();

        $slugEntity->setLangId($langId);
        $this->assertEquals($langId, $slugEntity->getLangId());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testSetTypeException()
    {
        $slugEntity = new Slug();

        $slugEntity->setType('I are baboon');
    }

    public function testSetType()
    {
        $type = 'text';
        $slugEntity = new Slug();

        $returnedEntity = $slugEntity->setType($type);

        $this->assertEquals($type, $slugEntity->getType());
        $this->assertInstanceOf('Food\AppBundle\Entity\Slug', $returnedEntity);
    }

    public function testName()
    {
        $name = 'dasName';

        $slugEntity = new Slug();

        $slugEntity->setName($name);
        $this->assertEquals($name, $slugEntity->getName());
    }

    public function testSetOrigName()
    {
        $originalName = 'Tekštas';
        $slugEntity = new Slug();

        $returnedEntity = $slugEntity->setOrigName($originalName);

        $this->assertEquals($originalName, $slugEntity->getOrigName());
        $this->assertInstanceOf('Food\AppBundle\Entity\Slug', $returnedEntity);
    }

    public function testId()
    {
        $slugEntity = new Slug();

        $this->assertNull($slugEntity->getId());
    }
}
