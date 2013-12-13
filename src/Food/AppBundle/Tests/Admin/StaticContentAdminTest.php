<?php

namespace Food\AppBundle\Tests\Admin;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\AppBundle\Admin\Admin;
use Food\AppBundle\Service\UploadService;
use Food\UserBundle\Entity\User;

class StaticContentAdminTest extends \PHPUnit_Framework_TestCase
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


    public function testFixSlugs()
    {
        // TODO zis custom test on weekend
    }


}
