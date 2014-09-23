<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Food\OrderBundle\Form\NordeaBanklinkType;
use Food\OrderBundle\Service\Banklink\Nordea;
use Food\OrderBundle\Service\Events\BanklinkEvent;
use Food\OrderBundle\Controller\Traits\NordeaDecorator;

class NordeaController extends Controller
{
    // this trait adds methods that handle logic for this controller
    use NordeaDecorator;

    public function redirectAction($id)
    {
        $rcvId = $this->container->getParameter('nordea.banklink.rcv_id');

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
