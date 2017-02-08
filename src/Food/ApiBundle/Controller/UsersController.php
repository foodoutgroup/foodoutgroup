<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Food\UserBundle\Entity\User;
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
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:registerAction Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            $um = $this->getUserManager();
//            $dispatcher = $this->container->get('event_dispatcher');
            $translator = $this->get('translator');
            $miscUtil = $this->get('food.app.utils.misc');

            /**
             * @var User $user
             */
            $user = $um->createUser();
            // TODO issiaiskinti kur dinge FosUserEventai
//            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

            $phone = $this->getRequestParam('phone');
            $name = $this->getRequestParam('name');
            $email = $this->getRequestParam('email');
            $password = $this->getRequestParam('password');
            $birthday = $this->getRequestParam('birthday');
            $facebook_id = $this->getRequestParam('facebook_id');

            $nameParsed = $this->parseName($name);

            if (!empty($facebook_id)) {
                if (empty($email)) {
                    $email = $facebook_id . "@foodout.lt";
                }
                $password = $facebook_id;
                $this->validateUserRegister(
                    array(
                        'firstname' => $nameParsed['firstname'],
                        'email' => $email,
                        'facebook_id' => $facebook_id,
                    )
                );
            } else {
                $this->validateUserRegister(
                    array(
                        'firstname' => $nameParsed['firstname'],
                        'email' => $email,
                        'phone' => $phone,
                        'password' => $password,
                        'birthday' => $birthday,
                    )
                );
            }

            // User exists???
            if (!empty($facebook_id)) {
                $existingUser = $um->findUserBy(array('facebook_id' => $facebook_id));
            }

            if (!$existingUser) {
                $existingUser = $um->findUserByEmail($email);
            }

            // Check only for FB users xz about not FB users, ask Egle why "Temporary" check is disabled
            if ($existingUser && $existingUser->getFullyRegistered() && !empty($facebook_id)) {
                throw new ApiException(
                    'User '.$email.' exists',
                    409,
                    array(
                        'error' => 'User exists',
                        'email' => $email,
                        'firstname' => $nameParsed['firstname'],
                        'description' => $translator->trans('registration.user.exists'),
                    )
                );
            }

            // Temporary by Egle request allowing anonymous registers and orders
            /*if ($existingUser && $existingUser->getFullyRegistered()) {
                throw new ApiException(
                    'User '.$email.' exists',
                    409,
                    array(
                        'error' => 'User exists',
                        'description' => $translator->trans('registration.user.exists'),
                    )
                );
            } else*/
            if ($existingUser) {
                $user = $existingUser;
            }

            $user->setFirstname($nameParsed['firstname'])
                ->setPhone($miscUtil->formatPhone($phone, $this->container->getParameter('country')))
                ->setEmail($email);
            // $user->setRoles(array('ROLE_USER'));
            $user->addRole('ROLE_USER');
            $user->setEnabled(true);

            if (!empty($nameParsed['lastname'])) {
                $user->setLastname($nameParsed['lastname']);
            }

            if (!empty($facebook_id)) {
                $user->setFacebookId($facebook_id);
            }

            if (!$existingUser || $existingUser && !$existingUser->getFullyRegistered()) {
                if (!empty($password)) {
                    $user->setPlainPassword($password)
                        ->setFullyRegistered(true);

                    // TODO turi ateiti emailas apie registracija - kolkas neeina :(
                    // TODO reikia issiaiskinti kur dingo FosUserEventai...
//                $event = new UserEvent($user, $request);
//                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
                } else {
                    $user->setPlainPassword('new-user')
                        ->setFullyRegistered(false);
                }
            }

            if (!empty($birthday)) {
                $user->setBirthday(new \DateTime($birthday));
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
                'refresh_token' => '',
                'isRealEmailSet' => $this->get('food_api.api')->isRealEmailSet($user)
            );

        } catch (ApiException $e) {
            $this->get('logger')->error('Users:registerAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:registerAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:registerAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:registerAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:registerAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
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
    public function register2Action(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:register2Action Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            $um = $this->getUserManager();
//            $dispatcher = $this->container->get('event_dispatcher');
            $translator = $this->get('translator');
            $miscUtil = $this->get('food.app.utils.misc');

            /**
             * @var User $user
             */
            $user = $um->createUser();
            // TODO issiaiskinti kur dinge FosUserEventai
//            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

            $phone = $this->getRequestParam('phone');
            $name = $this->getRequestParam('name');
            $email = $this->getRequestParam('email');
            $password = $this->getRequestParam('password');
            $birthday = $this->getRequestParam('birthday');
            $facebook_id = $this->getRequestParam('facebook_id');

            if (empty($phone)) {
                throw new ApiException(
                    'Unauthorized',
                    401,
                    [
                        'error'       => 'Missing phone number',
                        'description' => $this->container->get('translator')->trans('api.orders.user_phone_empty')
                    ]
                );
            }

            $country = $this->container->getParameter('country');
            $miscUtils = $this->container->get('food.app.utils.misc');
            if (!$miscUtils->isMobilePhone($phone, $country)) {
                throw new ApiException(
                    'Unauthorized',
                    401,
                    [
                        'error'       => 'Invalid phone number',
                        'description' => $this->container->get('translator')->trans('api.orders.user_phone_invalid')
                    ]
                );
            }

            $nameParsed = $this->parseName($name);

            if (!empty($facebook_id)) {
                if (empty($email)) {
                    $email = $facebook_id . "@foodout.lt";
                }
                $password = $facebook_id;
                $this->validateUserRegister(
                    array(
                        'firstname' => $nameParsed['firstname'],
                        'email' => $email,
                        'facebook_id' => $facebook_id,
                    )
                );
            }

            $this->validateUserRegister(
                array(
                    'firstname' => $nameParsed['firstname'],
                    'email' => $email,
                    'phone' => $phone,
                    'password' => $password,
                    'birthday' => $birthday,
                )
            );

            // User exists???
            if (!empty($facebook_id)) {
                $existingUser = $um->findUserBy(array('facebook_id' => $facebook_id));
            } else {
                $existingUser = $um->findUserByEmail($email);
            }

            // Check only for FB users xz about not FB users, ask Egle why "Temporary" check is disabled
            /*if ($existingUser && $existingUser->getFullyRegistered() && !empty($facebook_id)) {
                throw new ApiException(
                    'User '.$email.' exists',
                    409,
                    array(
                        'error' => 'User exists',
                        'email' => $email,
                        'firstname' => $nameParsed['firstname'],
                        'description' => $translator->trans('registration.user.exists'),
                    )
                );
            }*/

            // Temporary by Egle request allowing anonymous registers and orders
            /*if ($existingUser && $existingUser->getFullyRegistered()) {
                throw new ApiException(
                    'User '.$email.' exists',
                    409,
                    array(
                        'error' => 'User exists',
                        'description' => $translator->trans('registration.user.exists'),
                    )
                );
            } else*/
            if ($existingUser) {
                $user = $existingUser;
            }

            $user->setFirstname($nameParsed['firstname'])
                ->setPhone($miscUtil->formatPhone($phone, $this->container->getParameter('country')))
                ->setEmail($email);
            // $user->setRoles(array('ROLE_USER'));
            $user->addRole('ROLE_USER');
            $user->setEnabled(true);

            if (!empty($nameParsed['lastname'])) {
                $user->setLastname($nameParsed['lastname']);
            }

            if (!empty($facebook_id)) {
                $user->setFacebookId($facebook_id);
            }

            if (!$existingUser || $existingUser && !$existingUser->getFullyRegistered()) {
                if (!empty($password)) {
                    $user->setPlainPassword($password)
                        ->setFullyRegistered(true);

                    // TODO turi ateiti emailas apie registracija - kolkas neeina :(
                    // TODO reikia issiaiskinti kur dingo FosUserEventai...
//                $event = new UserEvent($user, $request);
//                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);
                } else {
                    $user->setPlainPassword('new-user')
                        ->setFullyRegistered(false);
                }
            }

            if (!empty($birthday)) {
                $user->setBirthday(new \DateTime($birthday));
            }

            // User hash generation action
            if (!$this->getApiToken($request)) {
                $hash = $this->get('food_api.api')->generateUserHash($user);
                $user->setApiToken($hash);
                $user->setApiTokenValidity(new \DateTime('+1 week'));
            }

            $um->updateUser($user);

            $this->loginUser($user);

            $response = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'session_token' => $hash,
                'refresh_token' => '',
                'isRealEmailSet' => $this->get('food_api.api')->isRealEmailSet($user)
            );

        } catch (ApiException $e) {
            $this->get('logger')->error('Users:register2Action Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:register2Action Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:register2Action Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:register2Action Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:register2Action Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * User update action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:updateAction Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $this->get('food_api.api')->loginByHash($this->getApiToken($request));
            $miscUtil = $this->get('food.app.utils.misc');

            $um = $this->getUserManager();
            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            $phone = $this->getRequestParam('phone');
            $email = $this->getRequestParam('email');
            $name = $this->getRequestParam('name');

            $nameParsed = $this->parseName($name);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $this->validateUserCommon(
                array(
                    'firstname' => $nameParsed['firstname'],
                    'email' => $email,
                    'phone' => $phone,
                )
            );
            if (!empty($phone)) {
                $user->setPhone($miscUtil->formatPhone($phone, $this->container->getParameter('country')));
            }

            if (!empty($email)) {
                $user->setEmail($email);
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
                'isRealEmailSet' => $this->get('food_api.api')->isRealEmailSet($user)
            );
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:updateAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:updateAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:updateAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:updateAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:updateAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
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
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:changePasswordAction Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $this->get('food_api.api')->loginByHash($this->getApiToken($request));
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

            $response = ['status' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:changePasswordAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:changePasswordAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:changePasswordAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:changePasswordAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:changePasswordAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 204);
    }

    /**
     * User password reset action
     *
     * @param Request $request
     * @return Response
     */
    public function resetPasswordAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:resetPasswordAction Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            // TODO after testing - remove!
            $email = $this->getRequestParam('email');
            $sendResult = $this->get('food.reset_password')->sendEmail($email);

            if (!$sendResult) {
                throw new ApiException(
                    'User does not exist',
                    404,
                    array(
                        'error' => 'User does not exist',
                        'description' => null
                    )
                );
            }

            $response = ['status' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:resetPasswordAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:resetPasswordAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:resetPasswordAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:resetPasswordAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:resetPasswordAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 200);
    }

    /**
     * User login action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function loginAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:loginAction Request:', (array) $request);
        try {
            $this->parseRequestBody($request);
            $username = $this->getRequestParam('email');
            $password = $this->getRequestParam('password', 'new-user');
            $phone = $this->getRequestParam('phone');
            $facebook_id = $this->getRequestParam('facebook_id');

            $um = $this->getUserManager();
            $user = $um->findUserByUsername($username);
            if(!$user){
                $user = $um->findUserBy(array('facebook_id' => $facebook_id));
            }
            if(!$user){
                $user = $um->findUserByEmail($username);
            }
            if(!$user){
                $user = $um->findUserBy(array('phone' => $phone));
            }

            if(!$user instanceof User){
                throw new ApiException("User not found", 400, array('error' => 'User not found', 'description' => null));
            }

            if (!empty($facebook_id) && $user->getFacebookId() !== $facebook_id) {
                throw new ApiException("Wrong FacebookID", 400, array('error' => 'User not found, please fill the registration form', 'description' => null));
            } elseif (empty($facebook_id)) {
                if(!$this->checkUserPassword($user, $password)){
                    throw new ApiException("Wrong password", 400, array('error' => 'Wrong password', 'description' => null));
                }
            }

            // Check if session is started. Start if not started
            $session = $this->container->get('session');
            $sessionId = $session->getId();
            if (empty($sessionId)) {
                $session->start();
            }

            $this->loginUser($user);

            // User hash verfy action
            $hash = $this->get('food_api.api')->generateUserHash($user);
            $user->setApiToken($hash);
            $user->setApiTokenValidity(new \DateTime('+1 year'));

            $um->updateUser($user);

            $response = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
                'session_token' => $hash,
                'refresh_token' => '',
                'isRealEmailSet' => $this->get('food_api.api')->isRealEmailSet($user)
            );

        } catch (ApiException $e) {
            $this->get('logger')->error('Users:loginAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:loginAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:loginAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:loginAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:loginAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * Logout a user (dispatcher only)
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function logoutUserAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:logoutUserAction Request:', (array) $request);
        try {
            $this->get('food_api.api')->loginByHash($this->getApiToken($request));

            $um = $this->getUserManager();
            $security = $this->get('security.context');
            $success = false;

            $user = $security->getToken()->getUser();
            if ($user instanceof User) {
                $user->setApiTokenValidity(new \DateTime('-1 week'));
                $user->setApiToken('');
                $um->updateUser($user);
                $success = true;
            }

            $token = new AnonymousToken(null, new User());
            $security->setToken($token);
            $this->get('session')->invalidate();

            $response = array(
                'success' => $success,
            );
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:logoutUserAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:logoutUserAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:logoutUserAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:logoutUserAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:logoutUserAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    /**
     * @return bool
     */
    public function AllowToAccess()
    {
        $sc = $this->get('security.context');
        $user = $sc->getToken()->getUser();

        if ($user instanceof User) {
            return (
                $sc->isGranted('ROLE_SUPER_ADMIN')
                || $sc->isGranted('ROLE_ADMIN')
                || $sc->isGranted('ROLE_MODERATOR')
                || $sc->isGranted('ROLE_SUPPORT')
                || $sc->isGranted('ROLE_MARKETING')
                || $sc->isGranted('ROLE_EDITOR')
                || $sc->isGranted('ROLE_DISPATCHER')
            );
        }
        return false;
    }

    /**
     * @param User $user
     * @return array
     */
    private function formatUserData(User $user)
    {
        $userArray = [];
        if ($user instanceof User) {
            $userArray['id'] = $user->getId();
            $userArray['firstname'] = $user->getFirstname();
            $userArray['lastname'] = $user->getLastname();
            $userArray['username'] = $user->getUsername();
            $userArray['email'] = $user->getEmail();
            $userArray['phone'] = $user->getPhone();
            $userArray['enabled'] = $user->isEnabled();
            $userArray['locked'] = $user->isLocked();
            $userArray['noInvoice'] = $user->getNoInvoice();
            $userArray['noMinimumCart'] = $user->getNoMinimumCart();
            $userArray['isBussinesClient'] = $user->getIsBussinesClient();
            $userArray['companyName'] = $user->getCompanyName();
            $userArray['lastLogin'] = $user->getLastLogin();
        }
        return $userArray;
    }

    public function usersListAction($itemsPerPage, $pageNo, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:usersListAction Request: itemsPerPage - ' . $itemsPerPage . ', pageNo - ' . $pageNo, (array) $request);
        try {
            $response = array(
                'success' => false,
                'users' => [],
                'total_count' => 0,
            );

            $this->get('food_api.api')->loginByHash($this->getApiToken($request));

            if ($this->AllowToAccess()) {
                $usersRepo = $this->getDoctrine()->getManager()->getRepository('FoodUserBundle:User');
                $total_count = $usersRepo->getUsersCount();

                $users = $usersRepo->findBy(
                    [],
                    [],
                    $itemsPerPage,
                    ($pageNo - 1) * $itemsPerPage
                );

                if (count($users)) {
                    $usersArray = [];
                    foreach ($users as $user) {
                        $usersArray[] = $this->formatUserData($user);
                    }

                    $response = array(
                        'success' => true,
                        'users' => $usersArray,
                        'total_count' => $total_count,
                    );
                }
            }
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:usersListAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:usersListAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:usersListAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:usersListAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:usersListAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }


    /**
     * Logout a user
     *
     * @param Request $request
     * @return JsonResponse|Response
     */
    public function logoutAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:logoutAction Request:', (array) $request);
        try {
            $this->get('food_api.api')->loginByHash($this->getApiToken($request));

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

            $response = ['status' => true];
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:logoutAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:logoutAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:logoutAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:logoutAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:logoutAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 204);
    }

    /**
     * User information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function meAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Users:meAction Request:', (array) $request);
        try {
            $this->get('food_api.api')->loginByHash($this->getApiToken($request));

            $security = $this->get('security.context');
            $user = $security->getToken()->getUser();

            $response = array(
                'user_id' => $user->getId(),
                'phone' => $user->getPhone(),
                'name' => $user->getFullName(),
                'email' => $user->getEmail(),
            );
        } catch (ApiException $e) {
            $this->get('logger')->error('Users:meAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Users:meAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Users:meAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Users:meAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Users:meAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
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

        if (empty($data['email'])) {
            $error = array(
                'error' => 'Email empty',
                'description' => $translator->trans('registration.email.is_empty')
            );
        } elseif (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $error = array(
                'error' => 'Email invalid',
                'description' => $translator->trans('food.marketing.bad_email')
            );
        }

        if (empty($data['facebook_id']) && empty($data['phone'])) {
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

    /**
     * @param Request $request
     * @return array|string
     */
    protected function getApiToken(Request $request)
    {
        $token = $request->headers->get('X-API-Authorization');

        if (empty($token)) {
            $token = $request->headers->get('x-api-authorization');
        }

        // for dispatcher only
        if (empty($token)) {
            $token = $request->get('X-API-Authorization');
        }

        return $token;
    }
}
