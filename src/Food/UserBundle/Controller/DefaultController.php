<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        if ($form->isValid()) {
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->container->get('router')->generate('food_lang_homepage');
                $response = new Response('');
            }

            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        return $this->render(
            'FoodUserBundle:Default:register.html.twig',
            [
                'form' => $form->createView(),
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
            ]
        );
    }

    /**
     * @Route("/{_locale}/login", name="user_login")
     * @Template("FoodUserBundle:Default:login.html.twig")
     */
    public function loginAction()
    {
        return $this->render('FoodUserBundle:Default:login.html.twig');
    }

    /**
     * @Route("/{_locale}/profile", name="user_profile")
     * @Template("FoodUserBundle:Default:profile.html.twig")
     */
    public function profileAction()
    {
        // 
    }

    public function loginButtonAction()
    {
        $token = $this->get('security.context')->getToken();
        $user = $token->getUser();

        if ($user->hasRole('ROLE_USER')) {
            return $this->render('FoodUserBundle:Default:profile_button.html.twig');
        } else {
            return $this->render('FoodUserBundle:Default:login_button.html.twig');
        }
    }
}
