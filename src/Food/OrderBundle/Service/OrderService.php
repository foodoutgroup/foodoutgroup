<?php

namespace Food\OrderBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;

class OrderService extends ContainerAware
{
    private $order;

    private $localBiller = null;

    private $payseraBiller = null;

    /**
     * @param $id
     */
    public function getOrderById($id)
    {

    }

    public function getOrderByHash($hash)
    {

    }

    /**
     * @param null $localBiller
     */
    public function setLocalBiller($localBiller)
    {
        $this->localBiller = $localBiller;
    }

    /**
     * @return null
     */
    public function getLocalBiller()
    {
        if (empty($this->localBiller)) {
            $this->localBiller = new LocalBiller();
        }
        return $this->localBiller;
    }

    /**
     * @param null $payseraBiller
     */
    public function setPayseraBiller($payseraBiller)
    {
        $this->payseraBiller = $payseraBiller;
    }

    /**
     * @return null
     */
    public function getPayseraBiller()
    {
        if (empty($this->payseraBiller)) {
            $this->payseraBiller = new PaySera();
        }
        return $this->payseraBiller;
    }

    /**
     * @param string $type
     * @return BillingInterface
     */
    public function getBillingInterface($type = 'local')
    {
        switch($type) {
            case 'local':
                return $this->getLocalBiller();
                break;

            case 'paysera':
            default:
                return $this->getPayseraBiller();
                break;
        }
    }

    /**
     * @param int $orderId
     * @param string $billingType
     */
    public function billOrder($orderId, $billingType = 'paysera')
    {
        $order = $this->getOrderById($orderId);
        $biller = $this->getBillingInterface($billingType);

        $biller->setOrder($order);
        $biller->bill();
    }
}