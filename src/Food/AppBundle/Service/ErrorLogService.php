<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\ErrorLog;
use Food\DishesBundle\Entity\Place;
use Food\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ErrorLogService extends BaseService
{
    /**
     * @var object|\Symfony\Component\HttpFoundation\Request
     */
    private $request;

    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->request = $container->get('request');
    }

    /**
     * @deprecated  this mastered peace of shit.. from 2017-04-26
     */
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

    /**
     * @param User $user
     * @param $cart
     * @param Place $place
     * @param $source
     * @param $description
     */
    public function write($user, $cart, $place, $source, $description)
    {
        $error = new ErrorLog();
        $error->setIp($this->request->getClientIp());
        $error->setCart($cart);
        $error->setCreatedBy($user);
        $error->setPlace($place);
        $error->setCreatedAt(new \DateTime());
        $error->setUrl($this->request->getUri());
        $error->setSource($source);
        $error->setDescription($description);
        $error->setDebug(serialize($this->request));

        $this->em->persist($error);
        $this->em->flush();
    }
}