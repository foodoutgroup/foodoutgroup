<?php

namespace Food\ApiBundle\Controller;

use Food\UserBundle\Entity\User;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class UsersController extends Controller
{
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

    public function registerAction(Request $request)
    {
        $um = $this->getUserManager();
        $dispatcher = $this->container->get('event_dispatcher');
        /**
         * @var User $user
         */
        $user = $um->createUser();
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, new UserEvent($user, $request));

        // TODO validation
        $phone = $request->get('phone');
        $name = $request->get('name');
        $email = $request->get('email');
        $password = $request->get('password');

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
    }

    public function loginAction(Request $request)
    {
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
}
