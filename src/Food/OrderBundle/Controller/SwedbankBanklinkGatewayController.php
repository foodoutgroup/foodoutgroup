<?php

namespace Food\OrderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Food\OrderBundle\Controller\Decorators\PaymentLogDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway\SharedDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway\RedirectDecorator;
use Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway\ReturnDecorator;

class SwedbankBanklinkGatewayController extends Controller
{
    use PaymentLogDecorator;
    use SharedDecorator;
    use RedirectDecorator;
    use ReturnDecorator;

    public function redirectAction($id, $locale)
    {
        $response = $this->handleRedirect($id, $locale);
        return $response;
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

    public function callbackAction(Request $request)
    {
        return $this->handleCalback($request);
    }
}
