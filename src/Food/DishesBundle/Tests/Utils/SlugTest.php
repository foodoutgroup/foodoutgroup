<?php

namespace Food\DishesBundle\Tests\Service;

use Food\DishesBundle\Utils\Slug;

class SlugTest extends \PHPUnit_Framework_TestCase
{
    public function testSetGetSlug()
    {
        $expectedSlug = 'jopapa';

        $slugUtil = new Slug('lt');

        $emptySlug = $slugUtil->get();

        $slugUtil->set($expectedSlug);

        $gotSlug = $slugUtil->get();

        $this->assertEquals('', $emptySlug);
        $this->assertEquals($expectedSlug, $gotSlug);
    }

    public function testStringToSlug()
    {
        $slugUtil = new Slug('lt');

        $testPairs = array(
            'food' => 'food',
            'super-sushiai' => 'super-sushiai',
            'restoranas--kaniusnia-' => 'restoranas-kaniusnia-',
            'restoranas----auksinis--lotoso-lapas' => 'restoranas-auksinis-lotoso-lapas',
            'restoranas-"aukselis"' => 'restoranas-aukselis',
            'kaciuku-„oaze“' => 'kaciuku-oaze',
            'vodke+agurkai' => 'vodke-agurkai',
        );

        foreach($testPairs as $testString => $expectedSlug) {
            $gotSlug = $slugUtil->stringToSlug($testString);
            $this->assertEquals($expectedSlug, $gotSlug);
        }
    }
}
