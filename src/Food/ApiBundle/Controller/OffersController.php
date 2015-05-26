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
        try {
            $repo = $this->get('doctrine')->getRepository('FoodPlacesBundle:BestOffer');
            $offers = $repo->getActiveOffers($city);

            $offersToShow = array();

            foreach ($offers as $misterOffer) {
                $placeId = null;
                if ($misterOffer->getPlace()) {
                    $placeId = $misterOffer->getPlace()->getId();
                }

                $offersToShow[] = array(
                    'title' => $misterOffer->getTitle(),
                    'city' => $misterOffer->getCity(),
                    'place' => $placeId,
                    'active' => $misterOffer->getActive(),
                    'text' => $misterOffer->getText(),
                    'image' => $misterOffer->getWebPath(),
                    'image_type1' => $misterOffer->getWebPathThumb('type1')

                );
            }

            return new JsonResponse($offersToShow);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
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
