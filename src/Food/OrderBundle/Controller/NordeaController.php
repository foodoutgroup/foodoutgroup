<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Food\OrderBundle\Form\NordeaBanklinkType;
use Food\OrderBundle\Service\Banklink\Nordea;
use Food\OrderBundle\Service\Events\BanklinkEvent;
use Food\OrderBundle\Controller\Traits\Nordea as NordeaTraits;

class NordeaController extends Controller
{
    use NordeaTraits\RedirectTrait;
    use NordeaTraits\ReturnTrait;
    use NordeaTraits\CancelTrait;
    use NordeaTraits\RejectTrait;

    public function redirectAction($id)
    {
        list($view, $data) = $this->handleRedirectAction($id);
        return $this->render($view, $data);
    }

    public function returnAction(Request $request)
    {
        list($view, $data) = $this->handleReturnAction($request);
        return $this->render($view, $data);
    }

    public function cancelAction(Request $request)
    {
        list($view, $data) = $this->handleCancelAction($request);
        return $this->render($view, $data);
    }

    public function rejectAction(Request $request)
    {
        list($view, $data) = $this->handleRejectAction($request);
        return $this->render($view, $data);
    }
}
