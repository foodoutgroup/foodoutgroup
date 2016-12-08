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
        $startTime = microtime(true);
        $this->get('logger')->alert('StaticContent:processAction Request: id - ' . $id, (array) $request);
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($this->get('food.app.utils.misc')->isIpBanned($ip)) {
            $this->get('logger')->alert('StaticContent:processAction Request:', (array) $request);
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
                'content' => $this->container->get('food.app.utils.misc')->stripFaqVideo($staticPage->getContent()),
            );

        }  catch (ApiException $e) {
            $this->get('logger')->error('StaticContent:processAction Error1:' . $e->getMessage());
            $this->get('logger')->error('StaticContent:processAction Trace1:' . $e->getTraceAsString());
            return new JsonResponse($e->getErrorData(), $e->getStatusCode());
        } catch (\Exception $e) {
            $this->get('logger')->error('StaticContent:processAction Error2:' . $e->getMessage());
            $this->get('logger')->error('StaticContent:processAction Trace2:' . $e->getTraceAsString());

            return new JsonResponse(
                ['error' => $this->get('translator')->trans('general.error_happened')],
                500,
                array('error' => 'server error', 'description' => null)
            );
        }

        $this->get('logger')->alert('StaticContent:processAction Response:'. print_r($response, true));
        $this->get('logger')->alert('Timespent:' . round((microtime(true) - $startTime) * 1000, 2) . ' ms');
        return new JsonResponse($response);
    }
}
