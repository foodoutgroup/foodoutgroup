<?php

namespace Food\ApiBundle\Controller;

use Food\UserBundle\Entity\User;
//use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class UsersController extends Controller
{
    /**
     * @var array
     */
    private $requestParams = array();

    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    protected function loginUser(User $user)
    {
        $security = $this->get('security.context');
        $providerKey = $this->container->getParameter('fos_user.firewall_name');
        $roles = $user->getRoles();
        $token = new UsernamePasswordToken($user, null, $providerKey, $roles);
        $security->setToken($token);
    }

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
     *  - validation
     *  - success email
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function registerAction(Request $request)
    {
        try {
            $this->parseRequestBody($request);
            $um = $this->getUserManager();
            $dispatcher = $this->container->get('event_dispatcher');

            // TODO after testing - remove!
            $this->logActionParams('Register user action', $this->requestParams);

            /**
             * @var User $user
             */
            $user = $um->createUser();
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

            // TODO validation
            $phone = $this->getRequestParam('phone');
            $name = $this->getRequestParam('name');
            $email = $this->getRequestParam('email');
            $password = $this->getRequestParam('password');

            if (strpos($name, ' ') === false) {
                $user->setFirstname($name);
            } else {
                $names = explode(' ', $name);
                $user->setFirstname($names[0])
                    ->setLastname($names[1]);
            }

            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setRoles(array('ROLE_USER'));
            $user->setEnabled(true);

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
        } catch (\ShownApiException $e) {
            return new Response($e->getMessage(), 404);
        } catch (\Exception $e) {
            return new Response(
                $this->get('translator')->trans('general.error_happened'),
                404
            );
        }
    }

    /**
     * User update action
     * TODO:
     *  - validation
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAction(Request $request)
    {
        // TODO after testing - remove!
        $this->logActionParams('Update user action', $request->request->all());
        $token = $request->headers->get('X-API-Authorization');
        $this->get('food_api.api')->loginByHash($token);

        $um = $this->getUserManager();
        $security = $this->get('security.context');
        $user = $security->getToken()->getUser();

        $phone = $request->get('phone');
        if (!empty($phone)) {
            $user->setPhone($phone);
        }
        $name = $request->get('name');
        if (!empty($name)) {
            if (strpos($name, ' ') === false) {
                $user->setFirstname($name);
            } else {
                $names = explode(' ', $name);
                $user->setFirstname($names[0])
                    ->setLastname($names[1]);
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
    }

    /**
     * TODO:
     *  - pasword lenght validation
     *
     * @param Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return Response
     */
    public function changePasswordAction(Request $request)
    {
        // TODO after testing - remove!
        $this->logActionParams('Change password action', $request->request->all());
        $token = $request->headers->get('X-API-Authorization');
        $this->get('food_api.api')->loginByHash($token);

        $um = $this->getUserManager();
        $security = $this->get('security.context');
        $user = $security->getToken()->getUser();

        $userId = $request->get('user_id');
        $password = $request->get('passwors');

        if (empty($password)) {
            throw new NotFoundHttpException('Empty password');
        }
        if ($userId != $user->getId()) {
            throw new NotFoundHttpException('User id does not match');
        }

        $user->setPlainPasseword($password);

        $um->updateUser($user);

        return new Response('', 204);
    }

    /**
     * TODO - make this work :)
     *
     * @param Request $request
     * @return Response
     */
    public function resetPasswordAction(/*Request $request*/)
    {
        throw new NotFoundHttpException('Not implemented yet');
//        return new Response('', 204);
    }

    /**
     * User login action
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function loginAction(Request $request)
    {
        // TODO after testing - remove!
        $this->logActionParams('Login action', $request->request->all());
        $username = $request->get('email');
        $password = $request->get('password', 'new-user');
        $phone = $request->get('phone');

        $um = $this->getUserManager();
        $user = $um->findUserByUsername($username);
        if(!$user){
            $user = $um->findUserByEmail($username);
        }
        if(!$user){
            $user = $um->findUserBy(array('phone' => $phone));
        }

        if(!$user instanceof User){
            throw new NotFoundHttpException("User not found");
        }
        if(!$this->checkUserPassword($user, $password)){
            throw new NotFoundHttpException("Wrong password");
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
    }

    public function logoutAction(Request $request)
    {
        $token = $request->headers->get('X-API-Authorization');
        $this->get('food_api.api')->loginByHash($token);

        $um = $this->getUserManager();
        $security = $this->get('security.context');

        $user = $security->getToken()->getUser();
        $this->get('logger')->alert('++ bandom is sesijos gaut useri');
        if ($user instanceof User) {
            $this->get('logger')->alert('++ yr useris :)');
            $user->setApiTokenValidity(new \DateTime('-1 week'));
            $user->setApiToken('');
            $um->updateUser($user);
        }

        $token = new AnonymousToken(null, new User());
        $security->setToken($token);
        $this->get('session')->invalidate();

        return new JsonResponse(array('success' => true));
    }

    /**
     * User information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function meAction(Request $request)
    {
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
    }

    /**
     * PVZ kaip identifikuoti vartotoja pagal Authorization tokena
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function testTokenAuthAction(Request $request)
    {
        $token = $request->headers->get('X-API-Authorization');
        $this->get('food_api.api')->loginByHash($token);

        $security = $this->get('security.context');
        $user = $security->getToken()->getUser();

        return new JsonResponse(array('success' => true, 'userId' => $user->getId()));
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
     * @return mixed|null
     */
    public function getRequestParam($key)
    {
        if (isset($this->requestParams[$key])) {
            return $this->requestParams[$key];
        }

        return null;
    }

    /**
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
