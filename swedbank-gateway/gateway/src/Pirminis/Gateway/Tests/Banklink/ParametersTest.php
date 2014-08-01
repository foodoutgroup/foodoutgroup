<?php

namespace Pirminis\Gateway\Tests\Banklink;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pirminis\Gateway\Swedbank\Banklink\Request\Parameters;

class ParametersTest extends WebTestCase
{
    public function setUp()
    {
    }

    public function test_constructor()
    {
        $params = new Parameters();

        $this->assertSame(
            'Pirminis\Gateway\Swedbank\Banklink\Request\Parameters',
            get_class($params));
    }

    public function test_get_mandatory_parameters()
    {
        $params = new Parameters();

        $this->assertInternalType('array', $params->mandatory_params());
    }

    public function test_set_a_parameter()
    {
        $params = new Parameters();
        $mandatory_params = $params->mandatory_params();

        $return_result = $params->set($mandatory_params[0], 'John');

        $this->assertSame(
            'Pirminis\Gateway\Swedbank\Banklink\Request\Parameters',
            get_class($return_result));
    }

    public function test_get_a_parameter()
    {
        $params = new Parameters();
        $mandatory_params = $params->mandatory_params();

        $this->assertSame(null, $params->get($mandatory_params[0]));

        $params->set($mandatory_params[0], 12);

        $this->assertSame(12, $params->get($mandatory_params[0]));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_set_a_non_mandatory_parameter()
    {
        $params = new Parameters();

        $params->set('name', 'john');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function test_get_a_non_mandatory_parameter()
    {
        $params = new Parameters();

        $params->get('name');
    }
}
