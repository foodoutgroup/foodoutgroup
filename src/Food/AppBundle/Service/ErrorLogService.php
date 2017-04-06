<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\ErrorLog;
use Symfony\Component\DependencyInjection\ContainerAware;

class ErrorLogService extends BaseService
{
    public function saveErrorLog($userIp,$user,$cart,$place,$createdAt,$url,$source,$description,$debug){

        $error = new ErrorLog();
        $error->setIp($userIp);
        $error->setCart($cart);
        $error->setCreatedBy($user);
        $error->setPlace($place);
        $error->setCreatedAt($createdAt);
        $error->setUrl($url);
        $error->setSource($source);
        $error->setDescription($description);
        $error->setDebug($debug);

        $this->em->persist($error);
        $this->em->flush();
    }
}