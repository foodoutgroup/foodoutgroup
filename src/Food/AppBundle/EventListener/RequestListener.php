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
        $availableLocales = $this->container->getParameter('locales');
        $disabledLocales = $this->container->getParameter('locales_hidden');

        if(count($disabledLocales)) {
            foreach ($availableLocales as $key => $locale) {
                if (in_array($locale, $disabledLocales)) {
                    unset($availableLocales[$key]);
                }
            }
        }

        if (!in_array($request, $availableLocales)) {
            throw new NotFoundHttpException('Sorry page does not exist');
        }
    }

    public function onLateKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $requestedLocale = $request->headers->get('locale');

        if($requestedLocale != null ) {

            if(!in_array($requestedLocale, $this->container->getParameter('locales'))) {
                $requestedLocale = null;
            } else {
                $translatable = $this->container->get('gedmo.listener.translatable');
                $translatable->setTranslatableLocale($requestedLocale);
            }
        }
    }

}