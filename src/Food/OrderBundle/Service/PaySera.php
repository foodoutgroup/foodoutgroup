<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerAware;

class PaySera extends ContainerAware implements BillingInterface {

    /**
     * @var string
     */
    private $siteDomain = null;

    /**
     * @var int
     */
    private $projectId = null;

    /**
     * @var string
     */
    private $sightPassword = null;

    /**
     * @var Order
     */
    private $order = null;

    /**
     * @var int
     */
    private $test = 0;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param int $projectId
     */
    public function setProjectId($projectId)
    {
        $this->projectId = $projectId;
    }

    /**
     * @return int
     */
    public function getProjectId()
    {
        return $this->projectId;
    }

    /**
     * @param string $sightPassword
     */
    public function setSightPassword($sightPassword)
    {
        $this->sightPassword = $sightPassword;
    }

    /**
     * @return string
     */
    public function getSightPassword()
    {
        return $this->sightPassword;
    }

    /**
     * @param Order $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $test
     */
    public function setTest($test)
    {
        $this->test = $test;
    }

    /**
     * @return int
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param string $siteDomain
     */
    public function setSiteDomain($siteDomain)
    {
        $this->siteDomain = $siteDomain;
    }

    /**
     * @return string
     */
    public function getSiteDomain()
    {
        return $this->siteDomain;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @returns url
     */
    public function bill()
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->container->get('logger');
        $logger->alert('--====================================================');
        $order = $this->getOrder();

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me someting, but not order :(');
        }

        $logger->alert('++ Bandom bilinti orderi su Id: '.$order->getId());
        $logger->alert('-------------------------------------');

        $router = $this->container->get('router');
        $acceptUrl = $router->generate('paysera_accept', array('_locale' => $this->getLocale()), true);
        $cancelUrl = $router->generate(
                'paysera_cancel',
                array(
                    'hash' => $order->getOrderHash(),
                    '_locale' => $this->getLocale(),
                ),
            true
            );
        $callbackUrl = $router->generate('paysera_callback', array(), true);

        $evpParams = array(
            'projectid' => $this->getProjectId(),
            'sign_password' => $this->getSightPassword(),
            'orderid' => $order->getId(),
            'amount' => (int)round($order->getTotal()*100),
            'currency' => 'EUR', // TODO kai eisim i kita rinka
            'country' => 'LT', // TODO kai eisim i kita rinka
            'accepturl' => $acceptUrl,
            'cancelurl' => $cancelUrl,
            'callbackurl' => $callbackUrl,
            'test' => $this->getTest(),
            'time_limit' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        );

        $logger->alert('++ EVP paduodami paramsai:');
        $logger->alert(var_export($evpParams, true));

        $redirectUrl = $this->container->get('evp_web_to_pay.request_builder')
            ->buildRequestUrlFromData($evpParams);

        $logger->alert('-------------------------------------');
        $logger->alert('Suformuotas url: '.$redirectUrl);
        $logger->alert('-------------------------------------');

        return $redirectUrl;
    }

    public function rollback()
    {
        // TODO how do we rollback?
        throw new \Exception('Not implemented yet');
    }

}
