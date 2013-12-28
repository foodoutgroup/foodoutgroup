<?php

namespace Food\SmsBundle\Controller;

use Food\SmsBundle\Service\InfobipProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deliverio is sms provideriu apdorojimas
 * @package Food\SmsBundle\Controller
 */
class DeliveryController extends Controller
{
    public function indexAction(Request $request)
    {
        $messagingService = $this->container->get('food.messages');

        // TODO iskelti i services.yml, kad uzkrautu per ten :) gal :)
        $infobipProvider = new InfobipProvider();
        // For debuging only!! TODO turn off this damn thing
        $infobipProvider->setLogger($this->container->get('logger'));
        $infobipProvider->setDebugEnabled(true);

        $messagingService->setMessagingProvider($infobipProvider);

        // TODO finish with deliveries
        $messagingService->updateMessagesDelivery($request->getContent());

        return new Response("OK, response parsed");
    }
}
