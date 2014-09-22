<?php

namespace Food\OrderBundle\Controller\Banklinks;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Security\Acl\Exception\Exception;
use Food\OrderBundle\Form\NordeaBanklinkType;
use Food\OrderBundle\Service\Banklink\Nordea;
use Food\OrderBundle\Service\Events\BanklinkEvent;
use Food\OrderBundle\Controller\Banklinks\Traits\NordeaControllerDecorator;

class NordeaController extends Controller
{
    // this trait adds methods that handle logic for this controller
    use NordeaControllerDecorator;

    public function redirectAction($id, $rcvId)
    {
        list($view, $data) = $this->handleRedirectAction($id, $rcvId);

        return $this->render($view, $data);
    }

    public function returnAction(Request $request)
    {
        # code...
    }

    public function cancelAction(Request $request)
    {
        # code...
    }

    public function rejectAction(Request $request)
    {
        # code...
    }
}
