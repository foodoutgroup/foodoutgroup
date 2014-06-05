<?php

namespace Food\AppBundle\Tests\Entity;

use Food\AppBundle\Entity\Uploadable;

class UploadableTest extends \PHPUnit_Framework_TestCase
{
    public function testUploadDir()
    {
        $uploadDir = 'products/new';
        $uploadable = new Uploadable();

        $uploadable->setUploadDir($uploadDir);
        $this->assertEquals($uploadDir, $uploadable->getUploadDir());
    }

    public function testUpload()
    {
        $uploadField = 'logo';
        $uploadable = new Uploadable();

        $uploadable->setUploadableField($uploadField);
        $this->assertEquals($uploadField, $uploadable->getUploadableField());
    }

    public function testGetWebPath()
    {
        $uploadField = 'logo';
        $uploadDir = 'food';

        $webPathGood = 'food/main_logo.jpg';

        $uploadable = new UploadableDumbEntity();
        $uploadable->setUploadableField($uploadField);
        $uploadable->setUploadDir($uploadDir);

        $webPath = $uploadable->getWebPath();
        $this->assertEquals($webPathGood, $webPath);
    }
}

class UploadableDumbEntity extends Uploadable
{
    public function getLogo()
    {
        return 'main_logo.jpg';
    }
}
