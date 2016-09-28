<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ParamsController extends Controller
{
    public function showRatingAction()
    {
        $miscService = $this->get('food.app.utils.misc');

        $possibleDeliveryDelay = $miscService->getParam('showMobilePopup');

        $response = ['show' => (bool) $possibleDeliveryDelay];

        $this->get('logger')->debug('Params:showRatingAction Response:', print_r($response, true));
        return new JsonResponse($response);
    }
}
