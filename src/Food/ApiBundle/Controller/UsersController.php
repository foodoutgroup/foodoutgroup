<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Food\UserBundle\Entity\User;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class UsersController extends Controller
{
    /**
     * @var array
     */
    private $requestParams = array();

    /**
     * @return \FOS\UserBundle\Doctrine\UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * Logs in user
     * @param User $user
     */
    protected function loginUser(User $user)
    {
        $security = $this->get('security.context');
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $roles = $user->getRoles();
        $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
        $security->setToken($token);
    }

    /**
     * Is user password correct
     *
     * @param User $user
     * @param string $password
     * @return bool
     */
    protected function checkUserPassword(User $user, $password)
    {
        $factory = $this->get('security.encoder_factory');
        $encoder = $factory->getEncoder($user);
        if(!$encoder){
            return false;
        }
        return $encoder->isPasswordValid($user->getPassword(), $password, $user->getSalt());
    }

    /**
     * User register
     *
     * TODO:
     *  - success email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registerAction(Request $request)
    {
        try {
            $this->parseRequestBody($request);
            $um = $this->getUserManager();
            $dispatcher = $this->container->get('event_dispatcher');
            $translator = $this->get('translator');

            // TODO after testing - remove!
            $this->logActionParams('Register user action', $this->requestParams);

            /**
             * @var User $user
             */
            $user = $um->createUser();
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

            $phone = $this->getRequestParam('phone');
            $name = $this->getRequestParam('name');
            $email = $this->getRequestParam('email');
            $password = $this->getRequestParam('password');

            $nameParsed = $this->parseName($name);

            $this->validateUserRegister(
                array(
                    'firstname' => $nameParsed['firstname'],
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password
                )
            );

            // User exists???
            $existingUser = $um->findUserByEmail($email);
            if ($existingUser) {
                throw new ApiException(
                    'User '.$email.' exists',
                    409,
                    array(
                        'error' => 'User exists',
                        'description' => $translator->trans('registration.user.exists'),
                    )
                );
            }

            $user->setFirstname($nameParsed['firstname'])
                ->setPhone($phone)
                ->setEmail($email);
            $user->setRoles(array('ROLE_USER'));
            $user->setEnabled(true);

            if (!empty($nameParsed['lastname'])) {
                $user->setLastname($nameParsed['lastname']);
            }

            if (!empty($password)) {
                $user->setPlainPassword($password)
                    ->setFullyRegistered(true);

                // TODO turi ateiti emailas apie registracija - kolkas neeina :(
                $event = new UserEvent($user, $request);
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
            } else {
                $user->setPlainPassword('new-user')
                    ->setFullyRegistered(false);
            }

            // User hash generation action
            $hash = $this->get('food_api.api')->generateUserHash($user);
            $user->setApiToken($hash);
            $user->setApiTokenValidity(new \DateTime('+1 week'));

            $um->updateUser($user);

            $this->loginUser($user);

            $response = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'session_token' => $hash,
                'refresh_token' => ''
            );

            return new JsonResponse($response);
        } catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * User update action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $this->logActionParams('Update user action', $this->requestParams);
            $token = $request->headers->get('X-API-Authorization');
            $this->get('food_api.api')->loginByHash($token);

            $um = $this->getUserManager();
            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            $phone = $this->getRequestParam('phone');
            $name = $this->getRequestParam('name');

            $nameParsed = $this->parseName($name);
            $this->validateUserCommon(
                array(
                    'firstname' => $nameParsed['firstname'],
                    'phone' => $phone,
                )
            );
            if (!empty($phone)) {
                $user->setPhone($phone);
            }

            if (!empty($name)) {
                $user->setFirstname($nameParsed['firstname']);
                if (!empty($nameParsed['lastname'])) {
                    $user->setLastname($nameParsed['lastname']);
                }
            }

            $um->updateUser($user);

            $response = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
            );

            return new JsonResponse($response);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * Chane
     *
     * @param Request $request
     *
     * @return Response
     */
    public function changePasswordAction(Request $request)
    {
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $this->logActionParams('Change password action', $this->requestParams);
            $token = $request->headers->get('X-API-Authorization');
            $this->get('food_api.api')->loginByHash($token);
            $translator = $this->get('translator');

            $um = $this->getUserManager();
            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            $userId = $this->getRequestParam('user_id');
            $password = $this->getRequestParam('password');

            if (empty($password)) {
                throw new ApiException(
                    'Validation failed', 400,
                    array(
                        'error' => 'Empty password',
                        'description' => $translator->trans('mobile.change_password.password.is_empty')
                    )
                );
            }
            if (mb_strlen($password) < 6) {
                throw new ApiException(
                    'Validation failed', 400,
                    array(
                        'error' => 'Password too short',
                        'description' => $translator->trans('registration.password.too_short')
                    )
                );
            }
            if ($userId != $user->getId()) {
                throw new ApiException(
                    'Validation failed', 400,
                    array(
                        'error' => 'User id does not match',
                        'description' => $translator->trans('mobile.change_password.user.id_dont_match')
                    )
                );
            }

            $user->setPlainPassword($password);

            $um->updateUser($user);

            return new Response('', 204);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * TODO - make this work :)
     *
     * @param Request $request
     * @return Response
     */
    public function resetPasswordAction(/*Request $request*/)
    {
        return new JsonResponse(
            'Not implemented yet',
            500,
            array('error' => 'Not implemented yet', 'description' => null)
        );
    }

    /**
     * User login action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $this->logActionParams('Login action', $this->requestParams);
            $username = $this->getRequestParam('email');
            $password = $this->getRequestParam('password', 'new-user');
            $phone = $this->getRequestParam('phone');

            $um = $this->getUserManager();
            $user = $um->findUserByUsername($username);
            if(!$user){
                $user = $um->findUserByEmail($username);
            }
            if(!$user){
                $user = $um->findUserBy(array('phone' => $phone));
            }

            if(!$user instanceof User){
                throw new ApiException("User not found", 400, array('error' => 'User not found', 'description' => null));
            }
            if(!$this->checkUserPassword($user, $password)){
                throw new ApiException("Wrong password", 400, array('error' => 'Wrong password', 'description' => null));
            }

            $this->loginUser($user);

            // User hash verfy action
            $hash = $this->get('food_api.api')->generateUserHash($user);
            $user->setApiToken($hash);
            $user->setApiTokenValidity(new \DateTime('+1 week'));

            $um->updateUser($user);

            $response = array(
                "user_id" => $user->getId(),
                "session_token" => $hash,
                "refresh_token" => ""
            );

            return new JsonResponse($response);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * Logout a user
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function logoutAction(Request $request)
    {
        try {
            $token = $request->headers->get('X-API-Authorization');
            $this->get('food_api.api')->loginByHash($token);

            $um = $this->getUserManager();
            $security = $this->get('security.context');

            $user = $security->getToken()->getUser();
            if ($user instanceof User) {
                $user->setApiTokenValidity(new \DateTime('-1 week'));
                $user->setApiToken('');
                $um->updateUser($user);
            }

            $token = new AnonymousToken(null, new User());
            $security->setToken($token);
            $this->get('session')->invalidate();

            return new Response('', 204);
        } catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * User information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function meAction(Request $request)
    {
        try {
            $token = $request->headers->get('X-API-Authorization');
            $this->get('food_api.api')->loginByHash($token);

            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            $userData = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
            );

            return new JsonResponse($userData);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * PVZ kaip identifikuoti vartotoja pagal Authorization tokena
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function testTokenAuthAction(Request $request)
    {
        try {
            $token = $request->headers->get('X-API-Authorization');
            $this->get('food_api.api')->loginByHash($token);

            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            return new JsonResponse(array('success' => true, 'userId' => $user->getId()));
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
    }

    /**
     * @param Request $request
     * @return array|mixed
     */
    public function parseRequestBody(Request $request)
    {
        $body = $request->getContent();

        if (!empty($body)) {
            $this->requestParams = json_decode($body, true);
        } else {
            $this->requestParams = array();
        }
    }

    /**
     * @param string $key
     * @param string|null $default
     * @return mixed|null
     */
    public function getRequestParam($key, $default=null)
    {
        if (isset($this->requestParams[$key])) {
            return $this->requestParams[$key];
        }

        return $default;
    }

    /**
     * @var array $data
     * @throws ApiException
     */
    public function validateUserCommon($data)
    {
        $translator = $this->get('translator');

        $error = array();

        if (empty($data['firstname'])) {
            $error = array(
                'error' => 'Firstname empty',
                'description' => $translator->trans('registration.firstname.is_empty')
            );
        }

        if (empty($data['phone'])) {
            $error = array(
                'error' => 'Phone empty',
                'description' => $translator->trans('registration.phone.is_empty')
            );
        }

        if (!empty($error)) {
            throw new ApiException('Validation exception', 400, $error);
        }
    }

    /**
     * @var array $data
     * @throws ApiException
     */
    public function validateUserRegister($data)
    {
        $translator = $this->get('translator');
        $this->validateUserCommon($data);

        $error = array();

        if (empty($data['email'])) {
            $error = array(
                'error' => 'Email empty',
                'description' => $translator->trans('registration.email.is_empty')
            );
        }
        if (!empty($data['password']) && mb_strlen($data['password']) < 6) {
            $error = array(
                'error' => 'Password too short',
                'description' => $translator->trans('registration.password.too_short')
            );
        }

        if (!empty($error)) {
            throw new ApiException('Validation exception', 400, $error);
        }
    }

    /**
     * @param string $name
     * @return array
     */
    protected function parseName($name)
    {
        $firstname = $name;
        $lastname = null;

        if (strpos($name, ' ') !== false) {
            $names = explode(' ', $name);
            $firstname = $names[0];
            $lastname = $names[1];
        }

        return array(
            'firstname' => $firstname,
            'lastname' => $lastname,
        );
    }

    /**
     * For debuging purpose only - log request data and action name for easy debug
     *
     * @param string $action
     * @param array $params
     */
    protected function logActionParams($action, $params)
    {
        $logger = $this->get('logger');

        $logger->alert('=============================== '.$action.' =====================================');
        $logger->alert('Request params:');
        $logger->alert(var_export($params, true));
        $logger->alert('=========================================================');
    }
}
