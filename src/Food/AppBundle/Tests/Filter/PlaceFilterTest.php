<?php

namespace Food\AppBundle\Tests\Filter;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\AppBundle\Filter\PlaceFilter;

class PlaceFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\HttpKernel\AppKernel
     */
    protected $kernel;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    public function setUp()
    {
        $this->kernel = new \AppKernel('test', true);
        $this->kernel->boot();

        $this->container = $this->kernel->getContainer();

        parent::setUp();
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testApplyUserException()
    {
        $authManager = $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface');
        $decisionManager = $this->getMock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface');
        $user = $this->getMock('\Food\UserBundle\Entity\User');
        $token = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken',
            array('getUser'),
            array($user, 'abc', 'cba')
        );
        $securityContext = $this->getMock(
            '\Symfony\Component\Security\Core\SecurityContext',
            array('getToken'),
            array($authManager, $decisionManager)
        );
        $queryBuilder = $this->getMock(
            '\Doctrine\ORM\QueryBuilder',
            array(),
            array($this->container->get('doctrine')->getManager())
        );
        $query = $this->getMock(
            '\Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery',
            array(),
            array($queryBuilder)
        );

        $placeFilter = new PlaceFilter($securityContext, 'place');

        $securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue(true));

        $placeFilter->apply($query);
    }

    /**
     * TODO susitvarkyti mockus ir pabaigti flow padengima testais
     * @depends testApplyUserException
     */
    public function testApplySkipAdmin()
    {
        $this->markTestSkipped('Bedos su uzmokinimu isGranted :| Kolkas sita testa paskipiniam :(');

        $authManager = $this->getMock('Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface');
        $decisionManager = $this->getMock('Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface');
        $user = $this->getMock(
            '\Food\UserBundle\Entity\User',
            array('getPlace')
        );
        $token = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken',
            array('getUser'),
            array($user, 'abc', 'cba')
        );
        $securityContext = $this->getMock(
            '\Symfony\Component\Security\Core\SecurityContext',
            array('getToken', 'isGranted'),
            array($authManager, $decisionManager)
        );
//        $securityContext->setToken($token);
        $queryBuilder = $this->getMock(
            '\Doctrine\ORM\QueryBuilder',
            array(),
            array($this->container->get('doctrine')->getManager())
        );
        $query = $this->getMock(
            '\Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery',
            array(),
            array($queryBuilder)
        );

        $placeFilter = new PlaceFilter($securityContext, 'place');

        $securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $securityContext->expects($this->once())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $user->expects($this->never())
            ->method('getPlace');

        $placeFilter->apply($query);
    }

}
