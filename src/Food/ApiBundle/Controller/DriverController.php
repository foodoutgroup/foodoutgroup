<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DriverController extends Controller
{
    /**
     * User information
     *
     * @param string $token
     * @param Request $request
     * @return JsonResponse
     */
    public function meAction($token, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Driver:meAction Request: token - ' . $token, (array) $request);
        try {
            $driver = $this->get('food_api.api')->getDriverByToken($token);

            $response = array(
                'id' => $driver->getId(),
                'type' => $driver->getType(),
                'extId' => $driver->getExtId(),
                'phone' => $driver->getPhone(),
                'name' => $driver->getName(),
                'city' => $driver->getCity(),
            );
        } catch (ApiException $e) {
            $this->get('logger')->error('Driver:meAction Error1:' . $e->getMessage());
            $this->get('logger')->error('Driver:meAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Driver:meAction Error2:' . $e->getMessage());
            $this->get('logger')->error('Driver:meAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('Driver:meAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
