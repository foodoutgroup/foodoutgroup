<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Food\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class OffersController extends Controller
{
    /**
     * @var array
     */
    private $requestParams = array();

    /**
     * User update action
     *
     * @param string|null $city
     * @return JsonResponse
     */
    public function getAction($city = null)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('Offers:getAction Request:' . $city);
        try {
            $repo = $this->get('doctrine')->getRepository('FoodPlacesBundle:BestOffer');
            $offers = $repo->getActiveOffers($city, true);

            $response = [];

            foreach ($offers as $misterOffer) {
                $placeId = null;
                if ($misterOffer->getPlace()) {
                    $placeId = $misterOffer->getPlace()->getId();
                }

                $response[] = [
                    'title' => $misterOffer->getTitle(),
                    'city' => $misterOffer->getCity(),
                    'place' => $placeId,
                    'active' => $misterOffer->getActive(),
                    'text' => $misterOffer->getText(),
                    'image' => $misterOffer->getWebPath(),
                    'image_type1' => $misterOffer->getWebPathThumb('type1')

                ];
            }

        }  catch (ApiException $e) {
            $this->get('logger')->error('Offers:getAction1 Error:' . $e->getMessage());
            $this->get('logger')->error('Offers:getAction1 Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('Offers:getAction2 Error:' . $e->getMessage());
            $this->get('logger')->error('Offers:getAction2 Trace:' . $e->getTraceAsString());
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
        $this->get('logger')->alert('Offers:getAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }


    /**
     * For debuging purpose only - log request data and action name for easy debug
     *
     * @param string $action
     * @param array $params
     */
    protected function logActionParams($action, $params)
    {
        $logger = $this->get('logger');

        $logger->alert('=============================== '.$action.' =====================================');
        $logger->alert('Request params:');
        $logger->alert(var_export($params, true));
        $logger->alert('=========================================================');
    }
}
