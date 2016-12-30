<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\CallLog;
use Symfony\Component\Security\Core\SecurityContext;

class DispatcherService extends BaseService
{
    protected $securityContext;

    public function saveCallLog($type, $number, $orderId = null)
    {
        $callLog = new CallLog();
        $callLog->setType($type);
        $callLog->setNumber($number);
        if ($orderId) {
            $order = $this->em->getRepository('FoodOrderBundle:Order')->find($orderId);
            $callLog->setOrderId($order);
        }
        $callLog->setCallDate(new \DateTime());
        $callLog->setUser($this->securityContext->getToken()->getUser());

        $this->em->persist($callLog);
        $this->em->flush($callLog);
    }

    /**
     * @param SecurityContext $securityContext
     */
    public function setSecurityContext($securityContext)
    {
        $this->securityContext = $securityContext;
    }
}