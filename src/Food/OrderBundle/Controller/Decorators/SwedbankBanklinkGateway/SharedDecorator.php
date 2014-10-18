<?php

namespace Food\OrderBundle\Controller\Decorators\SwedbankBanklinkGateway;

trait SharedDecorator
{
    protected function findOrder($id)
    {
        return $this->get('doctrine.orm.entity_manager')
                    ->getRepository('FoodOrderBundle:Order')
                    ->find($id);
    }

    protected function getSuccessUrl($locale)
    {
        return $this->generateUrl('swedbank_gateway_success',
                                  ['_locale' => $locale],
                                  true);
    }

    protected function getFailureUrl($locale)
    {
        return $this->generateUrl('swedbank_gateway_failure',
                                  ['_locale' => $locale],
                                  true);
    }
}
