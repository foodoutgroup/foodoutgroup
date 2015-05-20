<?php

namespace Food\ApiBundle\Tests\Controller;

use Food\AppBundle\Test\WebTestCase;
use Food\UserBundle\Entity\User;

class UsersControllerTest extends WebTestCase
{
    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        parent::setUp();

        // Create user for testing pusposes
        if (empty($this->user)) {
            $this->user = $this->getContainer()->get('fos_user.user_manager')
                ->findUserByEmail('api_tester@foodout.lt');
        }
    }

    public function testUserRegisterEmptyData()
    {
        $this->client->request(
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

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Phone empty') !== false));

        // Test 2
        $this->client->request(
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

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Firstname empty') !== false));

        // Test 3
        $this->client->request(
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

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Email empty') !== false));
    }

    // testing that actions require login
    public function testUserActionsRequireLogin()
    {
        // User information action
        $this->client->request(
            'GET',
            '/api/v1/users/me'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::meAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Token is empty') !== false));

        // User update action
        $this->client->request(
            'PUT',
            '/api/v1/users'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::updateAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Token is empty') !== false));

        // changePassword action
        $this->client->request(
            'POST',
            '/api/v1/users/change_password'
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Token is empty') !== false));
    }

    public function testChangePasswordValidations()
    {
        // Test empty password
        $this->client->request(
            'POST',
            '/api/v1/users/change_password',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'user_id' => null,
                    'password' => '',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Empty password') !== false));

        // Test too short password
        $this->client->request(
            'POST',
            '/api/v1/users/change_password',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'user_id' => null,
                    'password' => '1234',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Password too short') !== false));

        // Test wrong user
        $this->client->request(
            'POST',
            '/api/v1/users/change_password',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'user_id' => null,
                    'password' => '123456',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'User id does not match') !== false));
    }

    public function testPasswordChange()
    {
        $oldPassword = $this->user->getPassword();
        $this->client->request(
            'POST',
            '/api/v1/users/change_password',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'user_id' => $this->user->getId(),
                    'password' => '1234567',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::changePasswordAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(204 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue($this->client->getResponse()->getContent() == '');

        $user = $this->getContainer()->get('fos_user.user_manager')->findUserByEmail($this->user->getEmail());

        $this->assertTrue(($user->getPassword() != $oldPassword));
    }

    public function testLoginActionFails()
    {
        // Test unknown user
        $this->client->request(
            'POST',
            '/api/v1/users/login',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'email' => 'non_existing@mail.com',
                    'phone' => '37061111000',
                    'password' => 'wrong'
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::loginAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'User not found') !== false));

        // Test wrong password
        $this->client->request(
            'POST',
            '/api/v1/users/login',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'email' => 'api_tester@foodout.lt',
                    'phone' => '37061111111',
                    'password' => 'wrong'
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::loginAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(400 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'Wrong password') !== false));
    }

    public function testSuccessfulApiLogin()
    {
        $expectedUserData = array(
            'user_id' => $this->user->getId(),
            'phone' => $this->user->getPhone(),
            'name' => $this->user->getFullName(),
            'email' => $this->user->getEmail(),
            'refresh_token' => ''
        );

        $this->client->request(
            'POST',
            '/api/v1/users/login',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            ),
            json_encode(
                array(
                    'email' => 'api_tester@foodout.lt',
                    'phone' => '37061111111',
                    'password' => '1234567'
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::loginAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $userData = json_decode($this->client->getResponse()->getContent(), true);

        // Hash is dynamic - dont compare it - just check if not empty
        $this->assertTrue(!empty($userData['session_token']));
        unset($userData['session_token']);
        $this->assertEquals($expectedUserData, $userData);
    }

    public function testRegistrationExistingUser()
    {
        $this->client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    "phone" => "37060000000",
                    "name" => "Testas testuoklis",
                    "email" => "api_tester@foodout.lt",
                    'password' => 'new_user',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(409 , $this->client->getResponse()->getStatusCode());
        $this->assertTrue((strpos($this->client->getResponse()->getContent(), 'User exists') !== false));
    }

    public function testRegistrationSuccessful()
    {
        $expectedUserData = array(
            "phone" => "37060000000",
            "name" => "Testas testuoklis",
            "email" => "api_register@foodout.lt",
            "refresh_token" => '',
        );

        $this->client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    "phone" => "37060000000",
                    "name" => "Testas testuoklis",
                    "email" => "api_register@foodout.lt",
                    'password' => 'new_user',
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $userData = json_decode($this->client->getResponse()->getContent(), true);
        $newUser = $this->getContainer()->get('fos_user.user_manager')->findUserByEmail($userData['email']);
        $expectedUserData['user_id'] = $newUser->getId();

        // Hash is dynamic - dont compare it - just check if not empty
        $this->assertTrue(!empty($userData['session_token']));
        unset($userData['session_token']);
        $this->assertEquals($expectedUserData, $userData);

        $this->assertEquals('Testas', $newUser->getFirstname());
        $this->assertEquals('testuoklis', $newUser->getLastname());
        $this->assertEquals('api_register@foodout.lt', $newUser->getEmail());
        $this->assertEquals('37060000000', $newUser->getPhone());


        // And now log out the new user
        $this->client->request(
            'DELETE',
            '/api/v1/users/session',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $newUser->getApiToken(),
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::logoutAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(204 , $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();
        $this->assertTrue(empty($content));

        //Reload the user to know newest data
        $newUserUpdated = $this->getContainer()->get('fos_user.user_manager')->findUserByEmail($newUser->getEmail());

        $now = new \DateTime("now");
        $now = $now->format("Y-m-d");
        $validity = $newUserUpdated->getApiTokenValidity()->format("Y-m-d");

        $apiToken = $newUserUpdated->getApiToken();
        $this->assertTrue(empty($apiToken));
        $this->assertTrue(($validity < $now));
    }

    /**
     * Test phone formating and birthday setting
     */
    public function testRegistrationUnformatedPhoneSuccessful()
    {
        $expectedUserData = array(
            "phone" => "37060000001",
            "name" => "Testas testuoklis2",
            "email" => "api_register2@foodout.lt",
            "refresh_token" => '',
        );

        $this->client->request(
            'POST',
            '/api/v1/users',
            array(),
            array(),
            array(),
            json_encode(
                array(
                    "phone" => "860000001",
                    "name" => "Testas testuoklis2",
                    "email" => "api_register2@foodout.lt",
                    'password' => 'new_user',
                    'birthday' => '1986-01-01'
                )
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::registerAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $userData = json_decode($this->client->getResponse()->getContent(), true);
        $newUser = $this->getContainer()->get('fos_user.user_manager')->findUserByEmail($userData['email']);
        $expectedUserData['user_id'] = $newUser->getId();

        // Hash is dynamic - dont compare it - just check if not empty
        $this->assertTrue(!empty($userData['session_token']));
        unset($userData['session_token']);
        $this->assertEquals($expectedUserData, $userData);

        $this->assertEquals('Testas', $newUser->getFirstname());
        $this->assertEquals('testuoklis2', $newUser->getLastname());
        $this->assertEquals('api_register2@foodout.lt', $newUser->getEmail());
        $this->assertEquals('37060000001', $newUser->getPhone());
        $this->assertEquals('1986-01-01', $newUser->getBirthday()->format("Y-m-d"));


        // And now log out the new user
        $this->client->request(
            'DELETE',
            '/api/v1/users/session',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $newUser->getApiToken(),
            )
        );
    }

    public function testMeAction()
    {
        $expectedUserData = array(
            'user_id' => $this->user->getId(),
            'phone' => $this->user->getPhone(),
            'name' => $this->user->getFullName(),
            'email' => $this->user->getEmail(),
        );

        $this->client->request(
            'GET',
            '/api/v1/users/me',
            array(),
            array(),
            array(
                'HTTP_X-API-Authorization' => $this->user->getApiToken(),
            )
        );

        $this->assertEquals('Food\ApiBundle\Controller\UsersController::meAction', $this->client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(200 , $this->client->getResponse()->getStatusCode());

        $userData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($expectedUserData, $userData);
    }
}