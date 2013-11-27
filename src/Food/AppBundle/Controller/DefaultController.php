<?php

namespace Food\AppBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Gedmo\Translator\Entity\Translation;

class DefaultController extends Controller
{
    public function indexAction()
    {

        return $this->render('FoodAppBundle:Default:index.html.twig');
    }
}
