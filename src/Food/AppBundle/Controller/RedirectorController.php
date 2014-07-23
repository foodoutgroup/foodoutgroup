<?php

namespace Food\AppBundle\Controller;

use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectorController extends Controller
{
    public function indexAction($redirLocale)
    {
        return $this->redirect($this->generateUrl('food_lang_homepage'), 302);
    }

    public function slugAction($redirLocale, $slug)
    {
        return $this->redirect($this->generateUrl('food_slug', array('slug' => $slug)), 302);
    }
}