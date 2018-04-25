<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('FoodApiBundle:Default:index.html.twig');
    }

    /**
     * @param string $appType
     * @param Request $request
     * @return JsonResponse
     */
    public function getGlobalParametersAction($appType, Request $request)
    {
        $startTime = microtime(true);
        //$this->get('logger')->alert('Default:getGlobalParameters Request: app - ' . $appType, (array) $request);
        try {
            if (!empty($appType)) {
                $response = [];
                switch ($appType) {
                    case 'pp':
                        $placepointPrepareTimes = $this->get('food.app.utils.misc')->getParam('placepoint_prepare_times');
                        $placepointPrepareTimes = explode(',', $placepointPrepareTimes);
                        $response['prepareTimes'] = $placepointPrepareTimes;
                        $response['dispatcherContactPhone'] = $this->container->getParameter('dispatcher_contact_phone');
                        break;
                    default:
                        throw new ApiException('AppType does not exist.');
                        break;
                }
            } else {
                throw new ApiException('AppType not set.');
            }
        } catch (ApiException $e) {
            $this->get('logger')->error('Default:getGlobalParameters Error1:' . $e->getMessage());
            $this->get('logger')->error('Default:getGlobalParameters Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Default:getGlobalParameters Error2:' . $e->getMessage());
            $this->get('logger')->error('Default:getGlobalParameters Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        //$this->get('logger')->alert('Default:getGlobalParameters Response:'. print_r($response, true));
        //$this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 200, array('Access-Control-Allow-Origin' => '*'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */

    public function getDispatcherCitiesAction()
    {

        try {
            $response = [];
            $dispatcherCities = $this->container->get('doctrine')->getRepository('FoodAppBundle:City')->findBy(['showInDispatcher' => 1]);

            if ($dispatcherCities) {

                foreach ($dispatcherCities as $dispatcherCity) {
                    $respArray = [
                        'id' => $dispatcherCity->getId(),
                        'title' => $dispatcherCity->getTitle()
                    ];

                    array_push($response, $respArray);
                }
            }

        } catch (ApiException $e) {
            $this->get('logger')->error('Default:getDispatcherCities Error1:' . $e->getMessage());
            $this->get('logger')->error('Default:getDispatcherCities Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Default:getDispatcherCities Error2:' . $e->getMessage());
            $this->get('logger')->error('Default:getDispatcherCities Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        return new JsonResponse($response, 200, array('Access-Control-Allow-Origin' => '*'));
    }
}
