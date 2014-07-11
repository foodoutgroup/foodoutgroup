<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Food\UserBundle\Form\Type\ProfileFormType;
use Food\UserBundle\Form\Type\ProfileMegaFormType;
use Food\UserBundle\Form\Type\UserAddressFormType;
use Food\UserBundle\Form\Type\ChangePasswordFormType;
use Food\UserBundle\Entity\User;
use Food\UserBundle\Entity\UserAddress;

class DefaultController extends Controller
{
    /**
     * @Route("/{_locale}/register/create", name="user_register_create")
     * @Template()
     * @Method("POST")
     */
    public function registerCreateAction(Request $request)
    {
        $formFactory = $this->container->get('fos_user.registration.form.factory');
        $userManager = $this->container->get('fos_user.user_manager');
        $dispatcher = $this->container->get('event_dispatcher');

        $user = $userManager->createUser();
        $user->setUsername(uniqid('', true));
        $user->setEnabled(true);

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);
        $form->bind($request);

        $existingUser = $this->userExists($form->get('email')->getData());

        // rebind if user exists
        if ($existingUser) {
            $user = $existingUser;

            $form = $formFactory->createForm();
            $form->setData($user);
            $form->bind($request);
        }

        if ($form->isValid() && (!$existingUser || ($existingUser && !$existingUser->getFullyRegistered()))) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

            // manually set these flags. ->setEnabled is especially important for password update if user exists.
            $user->setEnabled(true);
            $user->setFullyRegistered(true);

            // finally update user
            $userManager->updateUser($user);

            // set noty notification for successful user registration
            $this
                ->container
                ->get('food.app.utils.notifications')
                ->setSuccessMessage(
                    $this
                        ->container
                        ->get('translator')
                        ->trans('general.noty.successful_user_registration')
                )
            ;

            $d = $request->get('fos_user_registration_form');

            $this->_notifyNewUser($user, $d['plainPassword']['first']);

            if (null === $response = $event->getResponse()) {
                $url = $this->container->get('router')->generate('food_lang_homepage');
                $response = new Response('');
            }

            $oldSid = $request->getSession()->getId();
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
            $newSid = $request->getSession()->getId();

            /**
             * @TODO - WATAFAK. Tik taip uzgesinom gaisra. Need to fix normaly :)
             */
            $this->get('food.cart')->migrateCartBetweenSessionIds($oldSid, $newSid);

            return $response;
        }

        if ($existingUser) {
            $form->get('email')->addError(new FormError('This user is already registered.'));
        }

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
                'submitted' => true
            ]
        );
    }

    /**
     * @param User $user
     * @param $pass
     */
    private function _notifyNewUser($user, $pass)
    {
        $ml = $this->get('food.mailer');

        $variables = array(
            'username' => $user->getUsername(),
            'password' => $pass,
            'login_url' => $this->generateUrl('food_lang_homepage', array(), true),
        );

        $ml->setVariables( $variables )->setRecipient( $user->getEmail(), $user->getEmail())->setId( 30009253 )->send();
    }

    /**
     * @Route("/{_locale}/register", name="user_register")
     * @Template("FoodUserBundle:Default:register.html.twig")
     */
    public function registerAction(Request $request)
    {
        $formFactory = $this->container->get('fos_user.registration.form.factory');
        $form = $formFactory->createForm();

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
                'submitted' => false
            ]
        );
    }

    /**
     * @Route("/{_locale}/login", name="user_login")
     * @Template("FoodUserBundle:Default:login.html.twig")
     */
    public function loginAction(Request $request)
    {
        $session = $request->getSession();

        $csrfToken = $this->container->has('form.csrf_provider')
            ? $this->container->get('form.csrf_provider')->generateCsrfToken('authenticate')
            : null;
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContext::LAST_USERNAME);

        return $this->render(
            'FoodUserBundle:Default:login.html.twig',
            [
                'csrf_token' => $csrfToken,
                'last_username' => $lastUsername
            ]
        );
    }

    /**
     * @Route("/{_locale}/profile/update", name="user_profile_update")
     * @Template("FoodUserBundle:Default:profile.html.twig")
     * @Method("POST")
     */
    public function profileUpdateAction(Request $request)
    {
        // services
        $userManager = $this->container->get('fos_user.user_manager');
        $em = $this->getDoctrine()->getManager();

        // data
        $user = $this->user();
        $address = $this->address($user);
        $cities = array('Vilnius' => 'Vilnius', 'Kaunas' => 'Kaunas');

        // embedded form
        $requestData = $request->request->get('food_user_profile');

        // mega form containts 3 embedded forms
        $form = $this->createProfileMegaForm($user, $address, $cities, $requestData['change_password']['current_password']);
        $form->handleRequest($request);

        // address validation
        if ($form->get('address')->isValid()) {
            $address
                ->setCity($form->get('address')->get('city')->getData())
                ->setAddress($form->get('address')->get('address')->getData())
            ;

            if (!$user->getDefaultAddress()) {
                $em->persist($address);
                $user->addAddress($address);
            }
        }

        // password validation
        if ($form->get('change_password')->isValid()) {
            $userManager->updateUser($user);
        }

        // main profile validation
        if ($form->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('user_profile'));
        }

        return [
            'form' => $form->createView(),
            'orders' => $this->get('food.order')->getUserOrders($user),
            'submitted' => true,
        ];
    }

    /**
     * @Route("/{_locale}/profile/{tab}", name="user_profile", defaults={"tab" = ""})
     * @Template("FoodUserBundle:Default:profile.html.twig")
     */
    public function profileAction($tab)
    {
        // services
        $security = $this->get('security.context');

        // page is accessible only to signed in users
        if (!$security->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('food_lang_homepage'));
        }

        // data
        $user = $this->user();
        $address = $this->address($user);
        $cities = array('Vilnius' => 'Vilnius', 'Kaunas' => 'Kaunas');

        // mega form containts 3 embedded forms
        $form = $this->createProfileMegaForm($user, $address, $cities, '');

        return [
            'form' => $form->createView(),
            'tab' => $tab,
            'orders' => $this->get('food.order')->getUserOrders($user),
            'submitted' => false,
        ];
    }

    public function loginButtonAction()
    {
        // due to mystery I will do stuff my way
        $user = $this->user();

        if ($user) {
            $user = $this
                ->getDoctrine()
                ->getRepository('FoodUserBundle:User')
                ->find($user->getId())
            ;
            $this->getDoctrine()->getManager()->refresh($user);
        }


        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->render(
                'FoodUserBundle:Default:profile_button.html.twig',
                array('user' => $user)
            );
        }

        return $this->render('FoodUserBundle:Default:login_button.html.twig');
    }

    private function userExists($email)
    {
        $existingUser = new ArrayCollection(
            $this
                ->getDoctrine()
                ->getRepository('FoodUserBundle:User')
                ->findByEmail($email)
        );

        return $existingUser->first();
    }

    private function user()
    {
        $sc = $this->get('security.context');

        if (!$sc->isGranted('ROLE_USER')) {
            return null;
        }

        return $sc->getToken()->getUser();
    }

    private function address(User $user)
    {
        if ($user->getDefaultAddress()) {
            return $user->getDefaultAddress();
        }

        $address = new UserAddress();

        $address
            ->setUser($user)
            ->setLat(0)
            ->setLon(0)
        ;

        return $address;
    }

    private function createProfileMegaForm($user, $address, $cities, $currentPassword)
    {
        $type = new ProfileMegaFormType(
            new ProfileFormType(get_class($user)),
            new UserAddressFormType($cities),
            new ChangePasswordFormType(get_class($user), $currentPassword)
        );
        $data = array(
            'profile' => $user,
            'address' => $address,
            'change_password' => $user
        );

        return $this->createForm($type, $data);
    }
}
