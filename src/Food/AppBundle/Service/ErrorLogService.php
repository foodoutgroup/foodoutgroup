<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\ErrorLog;
use Symfony\Component\DependencyInjection\ContainerAware;

class ErrorLogService extends ContainerAware
{
    private $user;

    public function setUserFromSecurityContext(SecurityContext $securityContext)
    {
        $this->user = $securityContext->getToken()->getUser();
    }

    public function saveErrorLog($source,$description,$debug){

        $description = is_array($description) ? implode(',', $description) : $description;

        $cart = $this->container->get('food.cart')->getSessionId();
        $cart = is_object($cart) ? $cart : null;
        $place = is_object($cart) ? $cart->getPlaceId() : null;

        $securityContext = $this->container->get('security.context');

        if ($securityContext->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $user = $securityContext->getToken()->getUser();
        }else{
            $user = null;
        }

        $error = new ErrorLog();
        $error->setIp($this->container->get('request')->getClientIp());
        $error->setCart($cart);
        $error->setCreatedBy($user);
        $error->setPlace($place);
        $error->setCreatedAt(new \DateTime('now'));
        $error->setUrl($this->container->get('request')->getRequestUri());
        $error->setSource($source);
        $error->setDescription($description);
        $error->setDebug($debug);

        $em = $this->container->get('doctrine')->getManager();
        $em->persist($error);
        $em->flush();
    }
}