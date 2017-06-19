<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class ParametersController extends Controller
{

    public function getParametersAction(Request $request)
    {

        try {
            $cityService = $this->get('food.city_service');
            $pedestrianActive = $cityService->getActivePedestrianCity();
            $listActive = $this->get('food.app.utils.misc')->getParam('pedestrian_filter_show');
            if(!empty($cityService) && $listActive){
                $response['pedestrian_cities'] = $pedestrianActive;
            }else{
                $response = [];
            }
        } catch (\Exception $e) {
            $this->get('logger')->error('Parameters:getParametersAction Error:' . $e->getMessage());
            $this->get('logger')->error('Parameters:getParametersAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        if (empty($response)) {
            $this->get('food.error_log')->write($this->getUser(), null, null, 'api_get_parameters', 'api_null_request');
        }


        //$this->get('logger')->alert('Address:findAddressAction Response:' . print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }



}
