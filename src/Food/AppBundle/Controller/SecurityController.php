<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Response;

class SecurityController extends Controller
{
    public function indexAction()
    {
        die("THIS IS DIE");
    }

    public function addUserAction($username, $email, $password)
    {
        $userManager = $this->get('fos_user.user_manager');
        //$user = $userManager->createUser();
        //$user = $userManager->findUserBy(array('id' => 1));
        //$user->setUsername($username);
        //$user->setEmail($email);
        //$user->setPassword($password);
        //$user->setEnabled(true);
        //$user->setRoles(array('ROLE_ADMIN'));


        //$userManager->updateUser($user);

        die("THIS IS THE END");
    }
}
