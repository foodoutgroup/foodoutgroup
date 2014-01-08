<?php

namespace Food\OrderBundle\Service;

use Food\OrderBundle\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerAware;

class PaySera extends ContainerAware implements BillingInterface {

    /**
     * @var string
     */
    private $siteUrl = null;

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
     * @param string $url
     * @return Paysera
     */
    public function setSiteUrl($url)
    {
        $this->siteUrl = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->siteUrl;
    }

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
     * @throws \InvalidArgumentException
     */
    public function bill()
    {
        $order = $this->getOrder();

        if (!($order instanceof Order)) {
            throw new \InvalidArgumentException('Sorry, You gave me someting, but not order :(');
        }

        $this->container->get('evp_web_to_pay.request_builder')->redirectPayment(array(
            'projectid' => $this->getProjectId(),
            'sign_password' => $this->getSightPassword(),
            'orderid' => $order->getId(),
            'amount' => $order->getAmount(),
            'currency' => 'LTL', // TODO kai eisim i kita rinka
            'country' => 'LT', // TODO kai eisim i kita rinka
            'accepturl' => $this->getSiteUrl().'/webtopay/accept/',
            'cancelurl' => $this->getSiteUrl().'/webtopay/cancel/',
            'callbackurl' => $this->getSiteUrl().'/webtopay/callback/',
            'test' => $this->getTest(), // TODO nuimti, kai nebereikes
        ));
    }

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