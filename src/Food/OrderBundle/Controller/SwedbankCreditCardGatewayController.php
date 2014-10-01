<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\SharedDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\RedirectDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\ReturnDecorator;

class SwedbankCreditCardGatewayController extends Controller
{
    use SharedDecorator;
    use RedirectDecorator;
    use ReturnDecorator;

    public function redirectAction($id)
    {
        $redirectResponse = $this->handleRedirect($id);
        return $redirectResponse;
    }

    public function successAction(Request $request)
    {
        list($view, $data) = $this->handleReturn($request);
        return $this->render($view, $data);
    }

    public function failureAction(Request $request)
    {
        return $this->successAction($request);
    }
}
