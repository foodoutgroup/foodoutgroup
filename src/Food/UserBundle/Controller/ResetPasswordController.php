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
        $formFactory = $this->container->get('fos_user.resetting.form.factory');
        $userManager = $this->container->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);
        $form = $formFactory->createForm();
        $form->setData($user);

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $userManager->updateUser($user);

                $url = $this->container
                            ->get('router')
                            ->generate('user_profile');

                $response = new RedirectResponse($url);

                return $response;
            }
        }

        return ['token' => $token, 'form' => $form->createView(), 'submitted' => $form->isSubmitted()];
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
}
