<?php

namespace Food\ApiBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Food\ApiBundle\Common\JsonRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Food\ApiBundle\Exceptions\ApiException;

class BasketController extends Controller
{
    /*
     * {"restaurant_id":"170", "items": [{"item_id":11069, "size_id": 14329, "count":1, "additional_info": ""}]}
     */
    public function createBasketAction(Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('createBasketAction Request:', (array) $request);
        try {
            $requestJson = new JsonRequest($request);

            // Check if session is started. Start if not started
            $session = $this->container->get('session');
            $sessionId = $session->getId();
            if (empty($sessionId)) {
                $session->start();
            }

            $response = $this->get('food_api.basket')->createBasketFromRequest($requestJson);
        }  catch (ApiException $e) {
            $this->get('logger')->error('createBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('createBasketAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('createBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('createBasketAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('createBasketAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function updateBasketAction($id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('updateBasketAction Request: id - '. $id, (array) $request);
        try{
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.basket')->updateBasketFromRequest($id, $requestJson));
        }  catch (ApiException $e) {
            $this->get('logger')->error('updateBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('updateBasketAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('updateBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('updateBasketAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('updateBasketAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function getBasketAction($id)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('getBasketAction Request:' . $id);
        try {
            $basket = $this->get('food_api.basket')->getBasket($id);
            $response = new JsonResponse($basket);
            $response->setMaxAge(1);
            $response->setSharedMaxAge(1);
            $date = new \DateTime();
            $response->setLastModified($date);
        }  catch (ApiException $e) {
            $this->get('logger')->error('getBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('getBasketAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('getBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('getBasketAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('getBasketAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new $response;
    }

    public function deleteBasketAction($id)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('deleteBasketAction Request: ' . $id);
        try {
            $this->get('food_api.basket')->deleteBasket($id);
            $response = '';
        }  catch (ApiException $e) {
            $this->get('logger')->error('deleteBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('deleteBasketAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('deleteBasketAction Error:' . $e->getMessage());
            $this->get('logger')->error('deleteBasketAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('deleteBasketAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response, 204);
    }

    public function updateBasketItemAction($id, $basket_item_id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('updateBasketItemAction Request: id - '. $id . ', basket_item_id - ' . $basket_item_id, (array) $request);
        try{
            $requestJson = new JsonRequest($request);
            $this->get('food_api.basket')->updateBasketItem($id, $basket_item_id, $requestJson);
            $response = $this->get('food_api.basket')->getBasket($id);
        }  catch (ApiException $e) {
            $this->get('logger')->error('updateBasketItemAction Error:' . $e->getMessage());
            $this->get('logger')->error('updateBasketItemAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('updateBasketItemAction Error:' . $e->getMessage());
            $this->get('logger')->error('updateBasketItemAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('updateBasketItemAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

    public function deleteBasketItemAction($id, $basket_item_id, Request $request)
    {
        $startTime = microtime(true);
        $this->get('logger')->alert('deleteBasketItemAction Request: id - '. $id . ', basket_item_id - ' . $basket_item_id, (array) $request);
        try {
            $requestJson = new JsonRequest($request);
            $this->get('food_api.basket')->deleteBasketItem($id, $basket_item_id, $requestJson);
            $response = $this->get('food_api.basket')->getBasket($id);
        }  catch (ApiException $e) {
            $this->get('logger')->error('deleteBasketItemAction Error:' . $e->getMessage());
            $this->get('logger')->error('deleteBasketItemAction Trace:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('deleteBasketItemAction Error:' . $e->getMessage());
            $this->get('logger')->error('deleteBasketItemAction Trace:' . $e->getTraceAsString());

            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('deleteBasketItemAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }

     /**
     * JSON beautifier
     *
     * @param string    The original JSON string
     * @param   string  Return string
     * @param string    Tab string
     * @return string
     */
    public function pretty_json($json, $ret= "\n", $ind="    ") {

        $beauty_json = '';
        $quote_state = FALSE;
        $level = 0;

        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++)
        {

            $pre = '';
            $suf = '';

            switch ($json[$i])
            {
                case '"':
                    $quote_state = !$quote_state;
                    break;

                case '[':
                    $level++;
                    break;

                case ']':
                    $level--;
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;

                case '{':

                    if ($i - 1 >= 0 && $json[$i - 1] != ',')
                    {
                        $pre = $ret;
                        $pre .= str_repeat($ind, $level);
                    }

                    $level++;
                    $suf = $ret;
                    $suf .= str_repeat($ind, $level);
                    break;

                case ':':
                    $suf = ' ';
                    break;

                case ',':

                    if (!$quote_state)
                    {
                        $suf = $ret;
                        $suf .= str_repeat($ind, $level);
                    }
                    break;

                case '}':
                    $level--;

                case ']':
                    $pre = $ret;
                    $pre .= str_repeat($ind, $level);
                    break;

            }

            $beauty_json .= $pre.$json[$i].$suf;

        }

        return $beauty_json;
    }
}
