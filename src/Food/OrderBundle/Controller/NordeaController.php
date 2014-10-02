<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\Nordea\SharedDecorator;
use Food\OrderBundle\Controller\Decorators\Nordea\RedirectDecorator;
use Food\OrderBundle\Controller\Decorators\Nordea\ReturnDecorator;

class NordeaController extends Controller
{
    use SharedDecorator;
    use RedirectDecorator;
    use ReturnDecorator;

    public function redirectAction($id)
    {
        list($view, $data) = $this->handleRedirect($id);
        return $this->render($view, $data);
    }

    public function returnAction(Request $request)
    {
        list($view, $data) = $this->handleReturn($request);
        return $this->render($view, $data);
    }

    public function cancelAction(Request $request)
    {
        return $this->returnAction($request);
    }

    public function rejectAction(Request $request)
    {
        return $this->returnAction($request);
    }
}
