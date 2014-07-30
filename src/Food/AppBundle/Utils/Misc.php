<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;

class Misc
{
    use Traits\Service;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

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
     * @return null|string
     */
    public function formatPhone($phone, $country)
    {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($phone, $country);
        } catch (\libphonenumber\NumberParseException $e) {
            return null;
        }

        $phoneFormated = $phoneUtil->format($numberProto, \libphonenumber\PhoneNumberFormat::E164);
        return str_replace('+', '', $phoneFormated);
    }

    /**
     * @param string $phone
     * @param string $country
     *
     * @return bool
     */
    public function isMobilePhone($phone, $country)
    {
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try {
            $numberProto = $phoneUtil->parse($phone, $country);
        } catch (\libphonenumber\NumberParseException $e) {
            return false;
        }

        $numberType = $phoneUtil->getNumberType($numberProto);

        if (in_array($numberType, array(\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE))) {
            return true;
        }

        return false;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public function isIpBanned($ip)
    {
        $repository = $this->container->get('doctrine')
            ->getRepository('FoodAppBundle:BannedIp');
        $isBanned = $repository->findOneBy(array('ip' => $ip, 'active' => true));

        if ($isBanned) {
            return true;
        }

        return false;
    }
}