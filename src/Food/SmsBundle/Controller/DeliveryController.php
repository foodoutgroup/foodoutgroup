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
    /**
     * @var MessagesService
     */
    private $messagingService = null;

    /**
     * @var \Food\SmsBundle\Service\SmsProviderInterface
     */
    private $provider = null;

    /**
     * @param \Food\SmsBundle\Service\SmsProviderInterface $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return \Food\SmsBundle\Service\SmsProviderInterface
     */
    public function getProvider()
    {
        if (empty($this->provider)) {
            $this->provider = new InfobipProvider();
        }
        return $this->provider;
    }

    /**
     * @param \Food\SmsBundle\Controller\MessagesService $messagingService
     */
    public function setMessagingService($messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * @return \Food\SmsBundle\Controller\MessagesService
     */
    public function getMessagingService()
    {
        if (empty($this->messagingService)) {
            $this->messagingService = $this->container->get('food.messages');
        }
        return $this->messagingService;
    }

    public function indexAction($request)
    {
        $messagingService = $this->getMessagingService();

        // TODO iskelti i services.yml, kad uzkrautu per ten :) gal :)
        $provider = $this->getProvider();
        // For debuging only!! TODO turn off this damn thing
        $provider->setLogger($this->container->get('logger'));
        $provider->setDebugEnabled(true);

        $messagingService->setMessagingProvider($provider);

        // TODO finish with deliveries
        $messagingService->updateMessagesDelivery($request->getContent());

        return new Response("OK, response parsed");
    }
}
