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
     * @var string
     */
    private $acceptUrl = null;

    /**
     * @var string
     */
    private $cancelUrl = null;

    /**
     * @var string
     */
    private $callbackUrl = null;

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
     * @param string $acceptUrl
     */
    public function setAcceptUrl($acceptUrl)
    {
        $this->acceptUrl = $acceptUrl;
    }

    /**
     * @return string
     */
    public function getAcceptUrl()
    {
        return $this->acceptUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $cancelUrl
     */
    public function setCancelUrl($cancelUrl)
    {
        $this->cancelUrl = $cancelUrl;
    }

    /**
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->cancelUrl;
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

        $siteDomain = 'http://'.$this->getSiteDomain();
        $router = $this->container->get('router');
        $acceptUrl = $siteDomain.$router->generate('paysera_accept', array('hash' => $order->getOrderHash()));
        $cancelUrl = $siteDomain.$router->generate('paysera_cancel', array('hash' => $order->getOrderHash()));
        $callbackUrl = $siteDomain.$router->generate('paysera_callback');

        $evpParams = array(
            'projectid' => $this->getProjectId(),
            'sign_password' => $this->getSightPassword(),
            'orderid' => $order->getId(),
            'amount' => $order->getAmount()*100,
            'currency' => 'LTL', // TODO kai eisim i kita rinka
            'country' => 'LT', // TODO kai eisim i kita rinka
            'accepturl' => $acceptUrl,
            'cancelurl' => $cancelUrl,
            'callbackurl' => $callbackUrl,
            'test' => $this->getTest(),
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
// TODO lower is useless?
    /**
     * @param $request
     * @return mixed
     */
    public function parseEvpResponse($request)
    {
        try {
            $callbackValidator = $this->container->get('evp_web_to_pay.callback_validator')
                ->validateAndParseData($request->query->all());
            $data = $callbackValidator->validateAndParseData($request->query->all());
        } catch (\Exception $e) {
            // refrow :(
        }

        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function validateCallback($data)
    {
        if ($data['status'] == 1) {
            return true;
        }

        return false;
    }

    public function rollback()
    {
        // TODO how do we rollback?
    }

}