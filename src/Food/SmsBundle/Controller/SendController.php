<?php

namespace Food\SmsBundle\Controller;

use Food\SmsBundle\Service\InfobipProvider;
use Food\SmsBundle\Service\SilverStreetProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for sending single message (by id) with enabled debugging
 *
 * @package Food\SmsBundle\Controller
 *
 * @codeCoverageIgnore
 */
class SendController extends Controller
{
    public function sendAction($messageId)
    {
        $messagingService = $this->container->get('food.messages');
        $message = $messagingService->getMessage($messageId);

        if (!$message) {
            return new Response("Message {$messageId} - NOT FOUND, looser");
        }

//        $infobipProvider = new InfobipProvider();
//        $infobipProvider->setApiUrl('http://api.infobip.com/api/v3/sendsms/json');
//        $infobipProvider->authenticate('skanu1', '119279');
//
//        $infobipProvider->setLogger($this->container->get('logger'));
//        $infobipProvider->setDebugEnabled(true);
//
//        $messagingService->setMessagingProvider($infobipProvider);

        $silverstreetProvider = new SilverStreetProvider();
        $silverstreetProvider->setApiUrl('http://api.silverstreet.com/send.php');
        $silverstreetProvider->authenticate('foodout', 'ZQg5ryLS');

        $silverstreetProvider->setLogger($this->container->get('logger'));
        $silverstreetProvider->setDebugEnabled(true);

        $messagingService->setMessagingProvider($silverstreetProvider);


        $messagingService->sendMessage($message);

        $messagingService->saveMessage($message);

        return new Response("Message {$messageId} - sent");
    }
}
