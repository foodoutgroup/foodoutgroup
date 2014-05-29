<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
use Symfony\Component\DependencyInjection\Container;

class Misc
{
    use Traits\Service;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $phone
     * @param string $country
     * @return mixed|null
     */
    public function formatPhone($phone, $country)
    {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            // TODO nepaliekam LT - eisim juk uzvaldyt pasaulio
            $numberProto = $phoneUtil->parse($phone, $country);
        } catch (\libphonenumber\NumberParseException $e) {
            return null;
        }

        $phoneFormated = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
        return str_replace('+', '', $phoneFormated);
    }
}