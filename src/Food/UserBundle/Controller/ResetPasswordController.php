<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            $this->service('food.reset_password')
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
     * @Route("/reset-password", name="food_user_resetting_reset_password")
     * @Template("FoodUserBundle:ResetPassword:reset.html.twig")
     */
    public function resetAction($token)
    {
        return [];
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
