<?php
namespace Food\AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestListener
{
    protected $container;

    public function __construct(ContainerInterface $container) // this is @service_container
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest()->getLocale();

//        var_dump($request);

        $availableLocales = $this->container->getParameter('locales');

        if (!in_array($request, $availableLocales)) {
            throw new NotFoundHttpException('Sorry page does not exist');
        }
    }

}