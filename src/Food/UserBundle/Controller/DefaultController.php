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

        $errors = [];

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        } $errors = array_unique($errors);

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
                'errors' => $errors,
            ]
        );
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
                'errors' => [],
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
     * @Route("/{_locale}/profile", name="user_profile")
     * @Template("FoodUserBundle:Default:profile.html.twig")
     */
    public function profileAction()
    {
        # todo
    }

    public function loginButtonAction()
    {
        if ($this->get('security.context')->isGranted('ROLE_USER')) {
            return $this->render('FoodUserBundle:Default:profile_button.html.twig');
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
}
