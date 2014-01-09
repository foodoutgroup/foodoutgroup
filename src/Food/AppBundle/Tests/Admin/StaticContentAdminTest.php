<?php

namespace Food\AppBundle\Tests\Admin;

require_once dirname(__DIR__).'/../../../../app/AppKernel.php';

use Food\AppBundle\Admin\StaticContentAdmin;
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
        $object = $this->getMock(
            '\Food\AppBundle\Entity\StaticContent',
            array('getTranslations', 'getTitle', 'getId')
        );

        // Mockinam belenka.. Bent kolkas
        $translations = $this->getMock(
            'RowForTest',
            array('getValues')
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $languageUtility = $this->getMock(
            'Food\AppBundle\Utils\Language',
            array('getAll'),
            array($this->container)
        );

        $slugUtility = $this->getMock(
            '\Food\DishesBundle\Utils\Slug',
            array('generateForTexts'),
            array('lt')
        );

        $locales = array('lt', 'en', 'ru');

        $translationObjects = array(
            new RowForTest('lt', 'title', 'Test titulas'),
            new RowForTest('lt', 'content', 'Test contentas'),
            new RowForTest('en', 'title', 'Test title'),
            new RowForTest('en', 'content', 'Test content'),
            new RowForTest('ru', 'title', 'Test nazvanije'),
            new RowForTest('ru', 'content', 'Test vnutrinaj tekst'),
        );

        $staticAdmin = new StaticContentAdmin(null, null, null);
        $staticAdmin->setContainer($container);

        $object->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue("Test title"));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_locales')
            ->will($this->returnValue($locales));

        $object->expects($this->once())
            ->method('getTranslations')
            ->will($this->returnValue($translations));

        $translations->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($translationObjects));

        $container->expects($this->at(1))
            ->method('get')
            ->with('food.app.utils.language')
            ->will($this->returnValue($languageUtility));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.dishes.utils.slug')
            ->will($this->returnValue($slugUtility));

        $languageUtility->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($locales));

        $object->expects($this->exactly(3))
            ->method('getId')
            ->will($this->returnValue(5));

        $slugUtility->expects($this->at(0))
            ->method('generateForTexts')
            ->with('lt', 5, 'Test titulas');

        $slugUtility->expects($this->at(1))
            ->method('generateForTexts')
            ->with('en', 5, 'Test title');

        $slugUtility->expects($this->at(2))
            ->method('generateForTexts')
            ->with('ru', 5, 'Test nazvanije');


        $staticAdmin->postUpdate($object);
    }

    public function testFixSlugsMissingOneLang()
    {
        $object = $this->getMock(
            '\Food\AppBundle\Entity\StaticContent',
            array('getTranslations', 'getTitle', 'getId')
        );

        // Mockinam belenka.. Bent kolkas
        $translations = $this->getMock(
            'RowForTest',
            array('getValues')
        );

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('getParameter', 'get')
        );

        $languageUtility = $this->getMock(
            'Food\AppBundle\Utils\Language',
            array('getAll'),
            array($this->container)
        );

        $slugUtility = $this->getMock(
            '\Food\DishesBundle\Utils\Slug',
            array('generateForTexts'),
            array('lt')
        );

        $locales = array('lt', 'en', 'ru');

        $translationObjects = array(
            new RowForTest('lt', 'title', 'Test titulas'),
            new RowForTest('lt', 'content', 'Test contentas'),
            new RowForTest('en', 'title', 'Test title'),
            new RowForTest('en', 'content', 'Test content'),
        );

        $staticAdmin = new StaticContentAdmin(null, null, null);
        $staticAdmin->setContainer($container);

        $object->expects($this->once())
            ->method('getTitle')
            ->will($this->returnValue("Test title"));

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_locales')
            ->will($this->returnValue($locales));

        $object->expects($this->once())
            ->method('getTranslations')
            ->will($this->returnValue($translations));

        $translations->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($translationObjects));

        $container->expects($this->at(1))
            ->method('get')
            ->with('food.app.utils.language')
            ->will($this->returnValue($languageUtility));

        $container->expects($this->at(2))
            ->method('get')
            ->with('food.dishes.utils.slug')
            ->will($this->returnValue($slugUtility));

        $languageUtility->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($locales));

        $object->expects($this->exactly(3))
            ->method('getId')
            ->will($this->returnValue(5));

        $slugUtility->expects($this->at(0))
            ->method('generateForTexts')
            ->with('lt', 5, 'Test titulas');

        $slugUtility->expects($this->at(1))
            ->method('generateForTexts')
            ->with('en', 5, 'Test title');

        $staticAdmin->postUpdate($object);
    }


}

class RowForTest {
    protected $locale = null;
    protected $field = null;
    protected $content = null;

    public function __construct($locale, $field, $content) {
        $this->locale = $locale;
        $this->field = $field;
        $this->content = $content;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getContent()
    {
        return $this->content;
    }
}