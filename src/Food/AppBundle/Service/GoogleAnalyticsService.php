<?php

namespace Food\AppBundle\Service;

class GoogleAnalyticsService
{
    const GA_PAGEVIEWS = 'ga:pageviews';
    const GA_UNIQUE_PAGEVIEWS = 'ga:uniquePageviews';
    const GA_USERS = 'ga:users';
    const GA_USER_TYPE = 'ga:userType';

    protected $serviceAccountName;
    protected $scopes;
    protected $privateKey;
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

    public function getUsers($from, $to)
    {
        $result = $this->getService()
                       ->data_ga
                       ->get(sprintf('ga:%s', $this->getViewId()),
                             $from,
                             $to,
                             static::GA_USERS)
                       ->getTotalsForAllResults();

        return !empty($result[static::GA_USERS])
               ? $result[static::GA_USERS]
               : null;
    }

    public function getReturningUsers($from, $to)
    {
        $result = $this->getService()
                       ->data_ga
                       ->get(sprintf('ga:%s', $this->getViewId()),
                             $from,
                             $to,
                             static::GA_USERS,
                             ['dimensions' => static::GA_USER_TYPE])
                       ->getRows();

        foreach ((array)$result as $value) {
            if (!empty($value[0]) && 'Returning Visitor' == $value[0]) {
                return $value[1];
            }
        }

        return -1;
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
