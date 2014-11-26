<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use FOS\UserBundle\Model\UserInterface;

class ResetPasswordController extends Controller
{
    /**
     * @Route("/request", name="food_user_resetting_request")
     * @Template("FoodUserBundle:ResetPassword:request.html.twig")
     */
    public function requestAction()
    {
        return [];
    }

    /**
     * @Route("/send-email", name="food_user_resetting_send_email")
     * @Method("POST")
     */
    public function sendEmailAction(Request $request)
    {
        $form = $this->form();
        $form->submit($request);

        if ($form->isValid() &&
            $this->get('food.reset_password')
                 ->sendEmail($form->get('email')->getData())) {
            return new Response(json_encode(['success' => true]));
        }

        return new Response(json_encode(['success' => false]));
    }

    /**
     * @Route("/check-email", name="food_user_resetting_check_email")
     * @Template("FoodUserBundle:ResetPassword:check_email.html.twig")
     */
    public function checkEmailAction()
    {
        return [];
    }

    /**
     * @Route("/reset-password/{token}", name="food_user_resetting_reset_password")
     * @Template("FoodUserBundle:ResetPassword:reset.html.twig")
     */
    public function resetAction(Request $request, $token)
    {
        // services
        $userManager = $this->container->get('fos_user.user_manager');
        $form = $this->container->get('fos_user.resetting.form');
        $formHandler = $this->container->get('fos_user.resetting.form.handler');
        $router = $this->container->get('router');

        // get user
        $user = $userManager->findUserByConfirmationToken($token);

        // get parameter
        $tokenTtl = $this->container
                         ->getParameter('fos_user.resetting.token_ttl');

        if (null === $user || !$user->isPasswordRequestNonExpired($tokenTtl)) {
            return ['token' => $token,
                    'form' => $form->createView(),
                    'submitted' => $form->isSubmitted()];
        }

        // process form using request
        $process = $formHandler->process($user);

        // if form is valid
        if ($process) {
            $url = $router->generate('user_profile');
            $response = new RedirectResponse($url);

            // don't forget to login user
            $this->authenticateUser($user, $response);

            return $response;
        }

        return ['token' => $token,
                'form' => $form->createView(),
                'submitted' => $form->isSubmitted()];
    }

    private function form()
    {
        $form = $this->get('form.factory')
                     ->createNamedBuilder('',
                                          'form',
                                          null,
                                          ['csrf_protection' => false])
                     ->add('email',
                           'email',
                           ['required' => true,
                            'constraints' => [new Email(),
                                              new NotBlank()]])
                     ->getForm()
        ;

        return $form;
    }

    protected function authenticateUser(UserInterface $user, Response $response)
    {
        // services
        $loginManager = $this->container->get('fos_user.security.login_manager');

        // parameter name
        $firewallName = $this->container->getParameter('fos_user.firewall_name');

        try {
            $loginManager->loginUser($firewallName, $user, $response);
        } catch (AccountStatusException $ex) {
            // We simply do not authenticate users which do not pass the user
            // checker (not enabled, expired, etc.).
        }
    }
}
