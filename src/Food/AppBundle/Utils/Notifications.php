<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;

class Notifications
{
    use Traits\Service;

    public function init()
    {
        return $this
            ->service('templating')
            ->render(
                'FoodAppBundle:Notifications:init.html.twig',
                [
                    'success_messages' => $this->getSuccessMessage()
                ]
            );
    }

    public function setSuccessMessage($message)
    {
        $this
            ->service('session')
            ->getFlashBag()
            ->set('success_messages', $message)
        ;
    }

    public function getSuccessMessage()
    {
        return $this
            ->service('session')
            ->getFlashBag()
            ->get('success_messages')
        ;
    }
}
