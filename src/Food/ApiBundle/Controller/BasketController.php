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
    public function createBasketAction(Request $request)
    {
        try {
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.basket')->createBasketFromRequest($requestJson));
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

    public function updateBasketAction($id, Request $request)
    {
        try{
            $requestJson = new JsonRequest($request);
            return new JsonResponse($this->get('food_api.basket')->updateBasketFromRequest($id, $requestJson));
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

    public function getBasketAction($id)
    {
        try {
            $basket = $this->get('food_api.basket')->getBasket($id);
            return new JsonResponse($basket);
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

    public function deleteBasketAction($id)
    {
        try {
            $this->get('food_api.basket')->deleteBasket($id);
            return new Response('', 204);
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

    public function updateBasketItemAction($id, $basket_item_id, Request $request)
    {
        try{
            $requestJson = new JsonRequest($request);
            $this->get('food_api.basket')->updateBasketItem($id, $basket_item_id, $requestJson);
            $basket = $this->get('food_api.basket')->getBasket($id);
            return new JsonResponse($basket);
        }  catch (ApiException $e) {
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } /**  catch (\Exception $e) {
            return new JsonResponse(
                $this->get('translator')->trans('general.error_happened'),
                500,
                array('error' => 'server error', 'description' => null)
            );
        }
         */
    }

    public function deleteBasketItemAction($id, $basket_item_id,Request $request)
    {
        try {
            $requestJson = new JsonRequest($request);
            $this->get('food_api.basket')->deleteBasketItem($id, $basket_item_id, $requestJson);
            $basket = $this->get('food_api.basket')->getBasket($id);
            return new JsonResponse($basket);
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