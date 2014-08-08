<?php

namespace Food\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
    public function sendEmailAction($id)
    {
        # code...
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
}
