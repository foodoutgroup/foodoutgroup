<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\PaymentLogDecorator;
use Food\OrderBundle\Controller\Decorators\Seb\SharedDecorator;
use Food\OrderBundle\Controller\Decorators\Seb\RedirectDecorator;
use Food\OrderBundle\Controller\Decorators\Seb\ReturnDecorator;

class SebController extends Controller
{
    use PaymentLogDecorator;
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
}
