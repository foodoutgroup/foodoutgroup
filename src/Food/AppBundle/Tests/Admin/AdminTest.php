<?php

namespace Food\AppBundle\Tests\Admin;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\AppBundle\Admin\Admin;
use Food\AppBundle\Service\UploadService;
use Food\UserBundle\Entity\User;

class AdminTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var User
     */
    protected $adminUser = null;

    /**
     * @var User
     */
    protected $moderatorUser = null;

    /**
     * @var User
     */
    protected $moderatorAdminUser = null;

    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        $this->adminUser = new User();
        $this->adminUser->setUsername('admin')
            ->setEnabled(true)
            ->addRole('ROLE_ADMIN');

        $this->moderatorUser = new User();
        $this->moderatorUser->setUsername('moderator')
            ->setEnabled(true)
            ->addRole('ROLE_MODERATOR');

        $this->moderatorAdminUser = new User();
        $this->moderatorAdminUser->setUsername('moderator')
            ->setEnabled(true)
            ->addRole('ROLE_MODERATOR')
            ->addRole('ROLE_ADMIN');

        parent::setUp();
    }

    public function testPrePersist()
    {
        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setUser($this->adminUser);

        $dish = $this->getMock(
            '\Food\DishesBundle\Entity\Dish',
            array('setCreatedAt', 'setCreatedBy')
        );

        $dish->expects($this->once())
            ->method('setCreatedAt');

        $dish->expects($this->once())
            ->method('setCreatedBy')
            ->with($this->equalTo($this->adminUser));

        $foodAdmin->prePersist($dish);
    }

    public function testPreUpdate()
    {
        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setUser($this->adminUser);

        $dish = $this->getMock(
            '\Food\DishesBundle\Entity\Dish',
            array('setEditedAt', 'setEditedBy', 'getDeletedAt')
        );

        $dish->expects($this->once())
            ->method('getDeletedAt')
            ->will($this->returnValue(null));

        $dish->expects($this->once())
            ->method('setEditedAt');

        $dish->expects($this->once())
            ->method('setEditedBy')
            ->with($this->equalTo($this->adminUser));

        $foodAdmin->preUpdate($dish);
    }

    public function testPreUpdateForDeleted()
    {
        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setUser($this->adminUser);

        $dish = $this->getMock(
            '\Food\DishesBundle\Entity\Dish',
            array('setEditedAt', 'setEditedBy', 'getDeletedAt')
        );

        $dish->expects($this->once())
            ->method('getDeletedAt')
            ->will($this->returnValue('2013-12-12 12:00:00'));

        $dish->expects($this->never())
            ->method('setEditedAt');

        $dish->expects($this->never())
            ->method('setEditedBy');

        $foodAdmin->preUpdate($dish);
    }

    public function testIsModerator()
    {
        // Betkokia klase apsimetam, kad nezaist su mockinimu symfonio briedo :D Negrazu, bet greita...
        $securityContext = $this->getMock(
            'Food\AppBundle\Admin',
            array('isGranted')
        );

        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setUser($this->moderatorUser);
        $foodAdmin->setSecurityContext($securityContext);

        $securityContext->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('ROLE_ADMIN'))
            ->will($this->returnValue(false));

        $securityContext->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('ROLE_MODERATOR'))
            ->will($this->returnValue(true));

        $isModerator1 = $foodAdmin->isModerator();
        $expected1 = true;

        // Second assert
        $securityContext = $this->getMock(
            'Food\AppBundle\Admin',
            array('isGranted')
        );
        $foodAdmin->setSecurityContext($securityContext);

        $securityContext->expects($this->at(0))
            ->method('isGranted')
            ->with($this->equalTo('ROLE_ADMIN'))
            ->will($this->returnValue(false));

        $securityContext->expects($this->at(1))
            ->method('isGranted')
            ->with($this->equalTo('ROLE_MODERATOR'))
            ->will($this->returnValue(false));

        $isModerator2 = $foodAdmin->isModerator();
        $expected2 = false;

        // Third assert
        $securityContext = $this->getMock(
            'Food\AppBundle\Admin',
            array('isGranted')
        );
        $foodAdmin->setSecurityContext($securityContext);

        $securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('ROLE_ADMIN'))
            ->will($this->returnValue(true));

        $isModerator3 = $foodAdmin->isModerator();
        $expected3 = false;


        $this->assertEquals($expected1, $isModerator1);
        $this->assertEquals($expected2, $isModerator2);
        $this->assertEquals($expected3, $isModerator3);
    }

    public function testIsAdmin()
    {
        // Betkokia klase apsimetam, kad nezaist su mockinimu symfonio briedo :D Negrazu, bet greita...
        $securityContext = $this->getMock(
            'Food\AppBundle\Admin',
            array('isGranted')
        );

        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setUser($this->moderatorUser);
        $foodAdmin->setSecurityContext($securityContext);

        $securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('ROLE_ADMIN'))
            ->will($this->returnValue(true));

        $isAdmin1 = $foodAdmin->isAdmin();
        $expected1 = true;

        // Second assert
        $securityContext = $this->getMock(
            'Food\AppBundle\Admin',
            array('isGranted')
        );
        $foodAdmin->setSecurityContext($securityContext);

        $securityContext->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo('ROLE_ADMIN'))
            ->will($this->returnValue(false));

        $isAdmin2 = $foodAdmin->isAdmin();
        $expected2 = false;

        $this->assertEquals($expected1, $isAdmin1);
        $this->assertEquals($expected2, $isAdmin2);
    }

    public function testContainerSetters()
    {
        $foodAdmin = new Admin(null, null, null);

        $foodAdmin->setContainer($this->container);
        $container = $foodAdmin->getContainer();

        $this->assertEquals($this->container, $container);
    }

    public function testUploadServiceSetters()
    {
        $foodAdmin = new Admin(null, null, null);
        $uploadService = new UploadService($this->container, 1);

        $foodAdmin->setUploadService($uploadService);
        $uploadServiceGot = $foodAdmin->getUploadService();

        $this->assertEquals($uploadService, $uploadServiceGot);
    }

    public function testSaveFile()
    {
        $request  = new \Symfony\Component\HttpFoundation\Request();

        $uploadService = $this->getMock(
            '\Food\AppBundle\Service\UploadService',
            array('setObject', 'upload'),
            array($this->container, 1)
        );

        $object = $this->getMock(
            'Food\DishesBundle\Entity\Place',
            array()
        );

        $foodAdmin = new Admin(null, null, null);
        $foodAdmin->setRequest($request);
        $foodAdmin->setUploadService($uploadService);

        $uploadService->expects($this->once())
            ->method('setObject')
            ->with($object);

        $uploadService->expects($this->once())
            ->method('upload')
            ->with('');

        $foodAdmin->saveFile($object);
    }


}
