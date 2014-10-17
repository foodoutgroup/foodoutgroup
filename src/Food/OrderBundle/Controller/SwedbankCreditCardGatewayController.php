<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\PaymentLogDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\SharedDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\RedirectDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankCreditCardGateway\ReturnDecorator;

class SwedbankCreditCardGatewayController extends Controller
{
    use PaymentLogDecorator;
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
        $response = $this->handleReturn($request);
        return $response;
    }

    public function failureAction(Request $request)
    {
        return $this->successAction($request);
    }
}
