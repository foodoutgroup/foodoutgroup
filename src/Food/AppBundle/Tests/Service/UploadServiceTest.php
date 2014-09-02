<?php

namespace Food\AppBundle\Tests\Service;

use Food\AppBundle\Service\UploadService;

class UploadServiceTest extends \PHPUnit_Framework_TestCase
{
    public function testSettersGetters()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock('\Food\AppBundle\Entity\Uploadable');

        $userId = 7;
        $userId2 = 16;
        $uploadableFieldSetter = 'setMagicMushroom';
        $uploadableFieldGetter = 'getFlyingCarpet';

        $uploadableService = new UploadService($container, $userId);

        $userIdGot1 = $uploadableService->getUserId();
        $this->assertEquals($userId, $userIdGot1);

        $uploadableService->setUserId($userId2);
        $userIdGot2 = $uploadableService->getUserId();
        $this->assertEquals($userId2, $userIdGot2);

        $containerGot1 = $uploadableService->getContainer();
        $this->assertEquals($container, $containerGot1);

        $uploadableService->setContainer(null);
        $containerGot2 = $uploadableService->getContainer();
        $this->assertNull($containerGot2);

        $uploadableService->setObject($theObject);
        $theObjectGot = $uploadableService->getObject();
        $this->assertEquals($theObject, $theObjectGot);

        $uploadableService->setUploadableFieldSetter($uploadableFieldSetter);
        $setterGot = $uploadableService->getUploadableFieldSetter();
        $this->assertEquals($uploadableFieldSetter, $setterGot);

        $uploadableService->setUploadableFieldGetter($uploadableFieldGetter);
        $getterGot = $uploadableService->getUploadableFieldGetter();
        $this->assertEquals($uploadableFieldGetter, $getterGot);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetUploadableFieldException()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $userId = 11;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->getUploadableField();
    }

    public function testGetUploadableFieldSetter()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getUploadableField')
        );

        $userId = 11;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);

        $theObject->expects($this->once())
            ->method('getUploadableField')
            ->will($this->returnValue('document'));

        $setterGot = $uploadableService->getUploadableFieldSetter();
        $this->assertEquals('setDocument', $setterGot);
    }

    public function testGetUploadableFieldGetter()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getUploadableField')
        );

        $userId = 11;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);

        $theObject->expects($this->once())
            ->method('getUploadableField')
            ->will($this->returnValue('logo'));

        $setterGot = $uploadableService->getUploadableFieldGetter();
        $this->assertEquals('getLogo', $setterGot);
    }

    public function testGetAbsolutePath()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getUploadDir', 'getLogo')
        );

        $userId = 11;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');

        $theObject->expects($this->once())
            ->method('getUploadDir')
            ->will($this->returnValue('uploads/products'));

        $theObject->expects($this->exactly(2))
            ->method('getLogo')
            ->will($this->returnValue('abuolys.jpg'));


        $absolutePath = $uploadableService->getAbsolutePath();

        $this->assertEquals('uploads/products/abuolys.jpg', $absolutePath);
    }

    public function testGenerateFileName()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getId', 'getFile')
        );

        $theFile = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();

        $userId = 11;
        $filename = '9_9d02c80338e3f7d43ea73f6c4c1fcf95.jpg';

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');

        $theObject->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(9));

        $theObject->expects($this->exactly(2))
            ->method('getFile')
            ->will($this->returnValue($theFile));

        $theFile->expects($this->once())
            ->method('getClientOriginalName')
            ->will($this->returnValue('superTurboLogotipas'));

        $theFile->expects($this->once())
            ->method('guessClientExtension')
            ->will($this->returnValue('jpg'));

        $fileNameGot = $uploadableService->generateFileName();

        $this->assertEquals($filename, $fileNameGot);
    }

    /**
     * Test when a new object and has no ID
     * @depends testGenerateFileName
     */
    public function testGenerateFileNameNoId()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getId', 'getFile')
        );

        $theFile = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();

        $userId = 11;
        $filename = 'noid_'.date('ymdhis').'_9d02c80338e3f7d43ea73f6c4c1fcf95.jpg';

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');

        $theObject->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(0));

        $theObject->expects($this->exactly(2))
            ->method('getFile')
            ->will($this->returnValue($theFile));

        $theFile->expects($this->once())
            ->method('getClientOriginalName')
            ->will($this->returnValue('superTurboLogotipas'));

        $theFile->expects($this->once())
            ->method('guessClientExtension')
            ->will($this->returnValue('jpg'));

        $fileNameGot = $uploadableService->generateFileName();

        $this->assertEquals($filename, $fileNameGot);
    }

    public function testUpload()
    {
        $this->markTestSkipped(
            'Idejom karpyma, sutvarkyti testa'
        );

        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $kernel = $this->getMockBuilder('\Symfony\Bundle\AsseticBundle\Tests\TestKernel')
            ->disableOriginalConstructor()
            ->getMock();

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getId', 'getFile', 'setFile', 'setLogo', 'getLogo', 'getUploadDir')
        );

        $theFile = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();

        $userId = 24;
        $filename = '9_9d02c80338e3f7d43ea73f6c4c1fcf95.jpg';

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');
        $uploadableService->setUploadableFieldSetter('setLogo');

        $theObject->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(9));

        $theObject->expects($this->once())
            ->method('getUploadDir')
            ->will($this->returnValue('products'));

        $theObject->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue($theFile));

        $theFile->expects($this->once())
            ->method('getClientOriginalName')
            ->will($this->returnValue('superTurboLogotipas'));

        $theFile->expects($this->once())
            ->method('guessClientExtension')
            ->will($this->returnValue('jpg'));

        $kernel->expects($this->once())
            ->method('getRootDir')
            ->will($this->returnValue('/kelias/namo'));

        $container->expects($this->once())
            ->method('get')
            ->with('kernel')
            ->will($this->returnValue($kernel));

        $theObject->expects($this->once())
            ->method('getLogo')
            ->will($this->returnValue(null));

        $theFile->expects($this->once())
            ->method('move')
            ->with('super/path/products', $filename);

        $theObject->expects($this->once())
            ->method('setLogo')
            ->with($filename);

        $theObject->expects($this->once())
            ->method('setFile')
            ->with(null);

        $uploadableService->upload('super/path/');
    }

    public function testUploadNoBasePath()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getId', 'getFile')
        );

        $theFile = $this->getMockBuilder('Symfony\Component\HttpFoundation\File\UploadedFile')
            ->disableOriginalConstructor()
            ->getMock();

        $userId = 24;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');
        $uploadableService->setUploadableFieldSetter('setLogo');

        $theObject->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue($theFile));

        $theObject->expects($this->never())
            ->method('getId');

        $uploadableService->upload(null);
    }

    public function testUploadNoFile()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');

        $theObject = $this->getMock(
            '\Food\AppBundle\Entity\Uploadable',
            array('getFile', 'getId')
        );

        $userId = 24;

        $uploadableService = new UploadService($container, $userId);
        $uploadableService->setObject($theObject);
        $uploadableService->setUploadableFieldGetter('getLogo');
        $uploadableService->setUploadableFieldSetter('setLogo');

        $theObject->expects($this->once())
            ->method('getFile')
            ->will($this->returnValue(null));

        $theObject->expects($this->never())
            ->method('getId');

        $uploadableService->upload(null);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage File can not be empty
     */
    public function testResizePhotoNoFile()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $userId = 24;

        $uploadService = new UploadService($container, $userId);
        $uploadService->resizePhoto('', 1234);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage I can not resize ir width is not specified
     */
    public function testResizePhotoNoSize()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $userId = 24;

        $uploadService = new UploadService($container, $userId);
        $uploadService->resizePhoto('abcd.jpg', null);
    }

    public function testGetMobileImageName()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $userId = 24;
        $expectedFileName1 = '/uploads/dishes/mobile_235_aspect_omfgwhatimage.jpg';
        $expectedFileName2 = '/uploads/places/mobile_1024_box_so_place.jpg';
        $expectedFileName3 = 'mobile_32_aspect_wow_icon.ico';

        $uploadService = new UploadService($container, $userId);

        $gotFileName1 = $uploadService->getMobileImageName('/uploads/dishes/omfgwhatimage.jpg', 235, false);
        $gotFileName2 = $uploadService->getMobileImageName('/uploads/places/so_place.jpg', 1024, true);
        $gotFileName3 = $uploadService->getMobileImageName('wow_icon.ico', 32, false);

        $this->assertEquals($expectedFileName1, $gotFileName1);
        $this->assertEquals($expectedFileName2, $gotFileName2);
        $this->assertEquals($expectedFileName3, $gotFileName3);
    }
}
