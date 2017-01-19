<?php

namespace Api\V2Bundle\Controller;

use Api\BaseBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->redirect("/");
    }
}
