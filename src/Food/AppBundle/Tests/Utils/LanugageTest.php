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

        $ltTest3 = 'Lietuviškas tekstas & simboliai # ąĄčČęĘėĖįĮšŠųŲūŪžŽ';
        $ltExpected3 = 'Lietuviskas tekstas and simboliai - aAcCeEeEiIsSuUuUzZ';

        $enTest1 = 'English text #1 & it should be fixed, man';
        $enExpected1 = 'english text -1 and it should be fixed, man';

        $enTest2 = 'english text that should be left untouched';
        $enExpected2 = 'english text that should be left untouched';

        $ruTest1 = 'Русский текст и иво алфавит, каторова нужно поченит';
        $ruExpected1 = 'russkii tekst i ivo alfavit, katorova nuzhno pochenit';

        $ruTest2 = 'ruskiy tekst, katorava nelzia trogat, mat t...';
        $ruExpected2 = 'ruskiy tekst, katorava nelzia trogat, mat t...';

        $ruTest3 = 'Русский текст и иво алфавит, каторова нужно поченит';
        $ruExpected3= 'Russkii tekst i ivo alfavit, katorova nuzhno pochenit';

        $special1 = 'Some text & special chars# that should be left alone';
        $specialExpected = 'some text & special chars# that should be left alone';

        $special2 = 'Some text & special chars# that should be left alone';
        $specialExpected2 = 'Some text & special chars# that should be left alone';

        $ltResult1 = $util->removeChars('lt', $ltTest1);
        $ltResult2 = $util->removeChars('lt', $ltTest2);
        $ltResult3 = $util->removeChars('lt', $ltTest3, false, true);

        $enResult1 = $util->removeChars('en', $enTest1);
        $enResult2 = $util->removeChars('en', $enTest2);

        $ruResult1 = $util->removeChars('ru', $ruTest1);
        $ruResult2 = $util->removeChars('ru', $ruTest2);
        $ruResult3 = $util->removeChars('ru', $ruTest3, false, true);

        $specialResult1 = $util->removeChars('en', $special1, true, false);
        $specialResult2 = $util->removeChars('en', $special2, false, false);

        $this->assertEquals($ltExpected1, $ltResult1);
        $this->assertEquals($ltExpected2, $ltResult2);
        $this->assertEquals($ltExpected3, $ltResult3);
        $this->assertEquals($enExpected1, $enResult1);
        $this->assertEquals($enExpected2, $enResult2);
        $this->assertEquals($ruExpected1, $ruResult1);
        $this->assertEquals($ruExpected2, $ruResult2);
        $this->assertEquals($ruExpected3, $ruResult3);
        $this->assertEquals($specialExpected, $specialResult1);
        $this->assertEquals($specialExpected2, $specialResult2);
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

    public function testNameFormaterLt()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\Container');
        $util = new Language($container);

        $nameToFormat1 = 'Mantas';
        $nameToFormat2 = 'Paulius';
        $nameToFormat3 = 'Eglė';
        $nameToFormat4 = 'Ona';
        $nameToFormat5 = 'Viktorija';
        $nameToFormat6 = 'Karolis';
        $nameToFormat7 = 'Balys';
        $nameToFormat8 = 'John';
        $nameToFormat10 = 'Thomas';

        $expectedName1 = 'Mantai';
        $expectedName2 = 'Pauliau';
        $expectedName3 = 'Egle';
        $expectedName4 = 'Ona';
        $expectedName5 = 'Viktorija';
        $expectedName6 = 'Karoli';
        $expectedName7 = 'Baly';
        $expectedName8 = 'John';
        $expectedName10 = 'Thomas';

        $formatedName1 = $util->getName($nameToFormat1, 'lt');
        $formatedName2 = $util->getName($nameToFormat2, 'lt');
        $formatedName3 = $util->getName($nameToFormat3, 'lt');
        $formatedName4 = $util->getName($nameToFormat4, 'lt');
        $formatedName5 = $util->getName($nameToFormat5, 'lt');
        $formatedName6 = $util->getName($nameToFormat6, 'lt');
        $formatedName7 = $util->getName($nameToFormat7, 'lt');
        $formatedName8 = $util->getName($nameToFormat8, 'lt');
        $formatedName9 = $util->getName($nameToFormat8, 'en');
        $formatedName10 = $util->getName($nameToFormat10);

        $this->assertEquals($expectedName1, $formatedName1);
        $this->assertEquals($expectedName2, $formatedName2);
        $this->assertEquals($expectedName3, $formatedName3);
        $this->assertEquals($expectedName4, $formatedName4);
        $this->assertEquals($expectedName5, $formatedName5);
        $this->assertEquals($expectedName6, $formatedName6);
        $this->assertEquals($expectedName7, $formatedName7);
        $this->assertEquals($expectedName8, $formatedName8);
        $this->assertEquals($expectedName8, $formatedName9);
        $this->assertEquals($expectedName10, $formatedName10);
    }
}
