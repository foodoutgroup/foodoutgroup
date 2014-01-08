<?php

namespace Food\OrderBundle\Service;

interface BillingInterface {

    public function setOrder($order);

    public function bill();

    public function rollback();

}