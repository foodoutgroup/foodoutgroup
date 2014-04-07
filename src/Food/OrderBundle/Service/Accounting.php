<?php

namespace Food\OrderBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Food\OrderBundle\Entity\Order;
use Food\OrderBundle\Entity\OrderAccounting;
use Symfony\Component\DependencyInjection\ContainerAware;

class AccountingService extends ContainerAware
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     *
     * @return $this
     */
    public function setEm($em)
    {
        $this->em = $em;
        return $this;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEm()
    {
        if (empty($this->em)) {
            $this->setEm($this->container->get('doctrine')->getManager());
        }
        return $this->em;
    }

    public function generateAccounting($order)
    {
        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Can not generate account if it is not an order');
        }

        $groupInfo = array(
//            Zemiau galimos reiksmes
//            'maistas' => 0,
//            'gerimai' => 0,
//            'alkoholis' => 0,
//            'pristatymas' => 0,
        );

        // TODO - PN, kaip su kainomis? mes viska laikome be PVM, o perduoti turime su PVM?
        foreach($order->getDetails() as $detail) {


        }


        $accounting = $this->createAccounting($order);

        $this->saveAccountingData($accounting);
    }

    /**
     * @param Order $order
     * @return OrderAccounting
     * @throws \InvalidArgumentException
     */
    public function createAccounting($order)
    {
        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Can not generate account if it is not an order');
        }

        $accounting = new OrderAccounting();
        $accounting->setOrder($order);

        return $accounting;
    }

    /**
     * @param OrderAccounting $accounting
     */
    public function saveAccountingData($accounting)
    {
        // TODO implement me
        $this->getEm()->persist($accounting);
        $this->getEm()->flush();
    }

    public function exportToCsv()
    {
        // TODO implement me
    }

    public function uploadCsv()
    {
        // TODO implement me
    }
}