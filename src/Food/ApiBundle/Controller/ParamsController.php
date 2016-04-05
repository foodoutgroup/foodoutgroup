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

        return new JsonResponse(array('show' => (bool) $possibleDeliveryDelay));
    }
}