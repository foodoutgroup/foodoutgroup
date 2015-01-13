<?php

namespace Food\AppBundle\Service;

class GoogleAnalyticsService
{
    const GA_PAGEVIEWS = 'ga:pageviews';
    const GA_UNIQUE_PAGEVIEWS = 'ga:uniquePageviews';

    protected $serviceAccountName;
    protected $scopes;
    protected $privateKey;
    protected $clientId;
    protected $developerKey;
    protected $viewId;

    public function getService()
    {
        $client = new \Google_Client();
        $client->setAssertionCredentials(
            new \Google_Auth_AssertionCredentials(
                $this->getServiceAccountName(),
                $this->getScopes(),
                $this->getPrivateKey()
            )
        );
        // $client->setClientId($this->getClientId());
        // $client->setDeveloperKey($this->getDeveloperKey());

        $service = new \Google_Service_Analytics($client);

        return $service;
    }

    public function getPageviews($from, $to)
    {
        $result = $this->getService()
                       ->data_ga
                       ->get(sprintf('ga:%s', $this->getViewId()),
                             $from,
                             $to,
                             static::GA_PAGEVIEWS)
                       ->getTotalsForAllResults();

        return !empty($result[static::GA_PAGEVIEWS])
               ? $result[static::GA_PAGEVIEWS]
               : null;
    }

    public function getUniquePageviews($from, $to)
    {
        $result = $this->getService()
                       ->data_ga
                       ->get(sprintf('ga:%s', $this->getViewId()),
                             $from,
                             $to,
                             static::GA_UNIQUE_PAGEVIEWS)
                       ->getTotalsForAllResults();

        return !empty($result[static::GA_UNIQUE_PAGEVIEWS])
               ? $result[static::GA_UNIQUE_PAGEVIEWS]
               : null;
    }

    public function setServiceAccountName($value)
    {
        $this->serviceAccountName = $value;
        return $this;
    }

    public function getServiceAccountName()
    {
        return $this->serviceAccountName;
    }

    public function setScopes(array $scopes)
    {
        $this->scopes = $scopes;
        return $this;
    }

    public function getScopes()
    {
        return $this->scopes;
    }

    public function setPrivateKey($base64Value)
    {
        $this->privateKey = base64_decode($base64Value);
        return $this;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setClientId($value)
    {
        $this->clientId = $value;
        return $this;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setDeveloperKey($value)
    {
        $this->developerKey = $value;
        return $this;
    }

    public function getDeveloperKey()
    {
        return $this->developerKey;
    }

    public function setViewId($value)
    {
        $this->viewId = $value;
        return $this;
    }

    public function getViewId()
    {
        return $this->viewId;
    }
}
