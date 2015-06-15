<?php

namespace Food\ApiBundle\Controller;

use Food\ApiBundle\Exceptions\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;


class StaticContentController extends Controller
{

    /**
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function processAction($id, Request $request)
    {
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($this->get('food.app.utils.misc')->isIpBanned($ip)) {
            return $this->redirect($this->generateUrl('banned'), 302);
        }

        try {
            $staticPage = $this->get('food.static')->getPage($id);
            if (!$staticPage) {
                throw new ApiException(
                    "Static content not found",
                    404,
                    array(
                        'error' => 'Static content not found',
                        'description' => null,
                    )
                );
            }

            $response = array(
                'title' => $staticPage->getTitle(),
                'content' => $staticPage->getContent(),
            );

            return new JsonResponse($response);
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
}