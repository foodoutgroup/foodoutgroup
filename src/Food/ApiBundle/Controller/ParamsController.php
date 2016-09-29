<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ParamsController extends Controller
{
    public function showRatingAction()
    {
        $startTime = microtime(true);
        $miscService = $this->get('food.app.utils.misc');

        $possibleDeliveryDelay = $miscService->getParam('showMobilePopup');

        $response = ['show' => (bool) $possibleDeliveryDelay];

        $this->get('logger')->alert('Params:showRatingAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
