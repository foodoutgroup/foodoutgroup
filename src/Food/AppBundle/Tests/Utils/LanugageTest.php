<?php

namespace Food\AppBundle\Tests\Utils;

use Food\AppBundle\Utils\Language;

class LanguageTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetContainers()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Language($container);

        $gotContainer1 = $util->getContainer();
        $this->assertEquals($container, $gotContainer1);

        $util->setContainer(null);
        $gotContainer2 = $util->getContainer();

        $this->assertNull($gotContainer2);
    }

    /**
     * @expectedException Exception
     */
    public function testRemoveCharsException()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Language($container);

        $util->removeChars('jap', 'arigato');
    }

    public function testRemoveChars()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Language($container);

        $ltTest1 = 'Lietuviškas tekstas & simboliai # ąĄčČęĘėĖįĮšŠųŲūŪžŽ';
        $ltExpected1 = 'lietuviskas tekstas and simboliai - aacceeeeiissuuuuzz';

        $ltTest2 = 'sveplas lietuviskas tekstas, kuris turi likti nesugadintas';
        $ltExpected2 = 'sveplas lietuviskas tekstas, kuris turi likti nesugadintas';

        $enTest1 = 'English text #1 & it should be fixed, man';
        $enExpected1 = 'english text -1 and it should be fixed, man';

        $enTest2 = 'english text that should be left untouched';
        $enExpected2 = 'english text that should be left untouched';

        $ruTest1 = 'Русский текст и иво алфавит, каторова нужно поченит';
        $ruExpected1 = 'russkii tekst i ivo alfavit, katorova nuzhno pochenit';

        $ruTest2 = 'ruskiy tekst, katorava nelzia trogat, mat t...';
        $ruExpected2 = 'ruskiy tekst, katorava nelzia trogat, mat t...';

        $ltResult1 = $util->removeChars('lt', $ltTest1);
        $ltResult2 = $util->removeChars('lt', $ltTest2);

        $enResult1 = $util->removeChars('en', $enTest1);
        $enResult2 = $util->removeChars('en', $enTest2);

        $ruResult1 = $util->removeChars('ru', $ruTest1);
        $ruResult2 = $util->removeChars('ru', $ruTest2);

        $this->assertEquals($ltExpected1, $ltResult1);
        $this->assertEquals($ltExpected2, $ltResult2);
        $this->assertEquals($enExpected1, $enResult1);
        $this->assertEquals($enExpected2, $enResult2);
        $this->assertEquals($ruExpected1, $ruResult1);
        $this->assertEquals($ruExpected2, $ruResult2);
    }

    public function testGetAll()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Language($container);

        $expectedLocales = array('lt', 'en', 'ru');

        $container->expects($this->once())
            ->method('getParameter')
            ->with('available_locales')
            ->will($this->returnValue($expectedLocales));

        $allLocales = $util->getAll();
        $this->assertEquals($expectedLocales, $allLocales);
    }
}
