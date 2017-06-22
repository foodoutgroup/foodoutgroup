<?php
namespace Food\AppBundle\Service;

class HookB2BClient {

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function build()
    {
        return [
            'template' => 'FoodAppBundle:Hook:b2b_client_' . strtolower($this->container->getParameter('country')) . '.html.twig'
        ];
    }
}
