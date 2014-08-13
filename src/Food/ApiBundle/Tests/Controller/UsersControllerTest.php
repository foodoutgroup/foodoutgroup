<?php

namespace Food\ApiBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    public function testUserRegisterEmptyData()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'password' => ''
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Phone empty') !== false));

        // Test 2
        $client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    'name' => '',
                    'email' => '',
                    'phone' => '37061514000',
                    'password' => ''
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Firstname empty') !== false));

        // Test 3
        $client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    'name' => 'Testuoklis',
                    'email' => '',
                    'phone' => '37061514000',
                    'password' => ''
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Email empty') !== false));
    }

    // testing that actions require login
    public function testUserActionsRequireLogin()
    {
        $client = static::createClient();

        // User information action
        $client->request(
            'GET',
            '/api/v1/users/me'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::meAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Token is empty') !== false));

        // User update action
        $client->request(
            'PUT',
            '/api/v1/users'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::updateAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Token is empty') !== false));

        // changePassword action
        $client->request(
            'POST',
            '/api/v1/users/change_password'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($client->getResponse()->getContent(), 'Token is empty') !== false));
    }
}