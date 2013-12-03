<?php

namespace Food\SmsBundle\Controller;

use Food\SmsBundle\Service\InfobipProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class SendController extends Controller
{
    public function sendAction($messageId) // Kodel nepareina??
    {
//        return $this->render('FoodSmsBundle:Default:index.html.twig', array('name' => $name));
        // TODO laikinai. Veliau reikes crono

        $messagingService = $this->container->get('food.messages');
        $message = $messagingService->getMessage($messageId);

        if (!$message) {
            return new Response("Message {$messageId} - NOT FOUND, looser");
        }

        // TODO iskelti i services.yml, kad uzkrautu per ten :) gal :)
        $infobipProvider = new InfobipProvider();
        $infobipProvider->setApiUrl('http://api.infobip.com/api/v3/sendsms/json');
        $infobipProvider->authenticate('skanu1', '119279');

        // For debuging only!! TODO turn off this damn thing
        $infobipProvider->setLogger($this->container->get('logger'));
        $infobipProvider->setDebugEnabled(true);

        $messagingService->setMessagingProvider($infobipProvider);
        $messagingService->sendMessage($message);

        $messagingService->saveMessage($message);


        return new Response("Message {$messageId} - sent");
    }
}
