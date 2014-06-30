<?php

namespace Food\SmsBundle\Controller;

use Food\SmsBundle\Service\InfobipProvider;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
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
     * @param \Food\SmsBundle\Service\MessagesService $messagingService
     */
    public function setMessagingService($messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * @return \Food\SmsBundle\Service\MessagesService
     */
    public function getMessagingService()
    {
        if (empty($this->messagingService)) {
            $this->messagingService = $this->container->get('food.messages');
        }
        return $this->messagingService;
    }

    public function indexAction($provider, Request $request)
    {
        $messagingService = $this->getMessagingService();

        if ($provider == 'silverstreet') {
            $this->setProvider($this->get('food.silverstreet'));
        }

        // TODO iskelti i services.yml, kad uzkrautu per ten :) gal :)
        $provider = $this->getProvider();
        // For debuging only!! TODO turn off this damn thing
        $provider->setLogger($this->container->get('logger'));
        $provider->setDebugEnabled(true);

        $messagingService->setMessagingProvider($provider);

        if ($provider == 'silverstreet') {
            $messagingService->updateMessagesDelivery(
                array(
                    'reference' => $request->get('REFERENCE'),
                    'status' => $request->get('STATUS'),
                    'reason' => $request->get('REASON'),
                    'destination' => $request->get('DESTINATION'),
                    'timestamp' => $request->get('TIMESTAMP'),
                    'operator' => $request->get('OPERATOR')
                )
            );
        } else {
            $messagingService->updateMessagesDelivery($request->getContent());
        }

        return new Response("OK, response parsed");
    }
}
