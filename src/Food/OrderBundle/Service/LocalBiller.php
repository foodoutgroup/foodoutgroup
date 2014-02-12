<?php

namespace Food\OrderBundle\Service;

class LocalBiller implements BillingInterface {

    /**
     * @var string
     */
    private $locale;

    public function setOrder($order)
    {

    }

    public function bill()
    {

    }

    public function rollback()
    {

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


}