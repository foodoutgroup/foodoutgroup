<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Entity\Param;
use Food\AppBundle\Traits;

class Misc
{
    use Traits\Service;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     * @var float
     */
    private $accountingEuroRate = 3.4528;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
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

    /**
     * Parses address string to street, house, flat numbers
     *
     * @param string $address
     * @return array
     */
    public function parseAddress($address)
    {
        if (!empty($address)) {
            preg_match('/\s(([0-9]{1,3}\s?[a-z]?)[-|\s]{0,4}([0-9]{0,3}))$/i', $address, $addrData);

            if (isset($addrData[0])) {
                $street = trim(str_replace($addrData[0], '', $address));
                $house = (!empty($addrData[2]) ? $addrData[2] : '');
                $flat = (!empty($addrData[3]) ? $addrData[3] : '');
            } else {
                $street = $address;
                $house = '';
                $flat = '';
            }
        } else {
            $street = '';
            $house = '';
            $flat = '';
        }

        return array(
            'street' => $street,
            'house' => $house,
            'flat' => $flat,
        );
    }

    /**
     * @param float $price
     * @return float
     */
    public function getEuro($price)
    {
        $euroPrice = $price / $this->accountingEuroRate;

        return round($euroPrice, 2);
    }

    /**
     * @param float $price
     * @return float
     */
    public function getLitas($price)
    {
        $litasPrice = $price * $this->accountingEuroRate;

        return round($litasPrice, 2);
    }

    /**
     * @param int|float $sum
     * @return string
     */
    public function priceToText($sum)
    {
        $translator = $this->getContainer()->get('translator');

        $numbers = $this->floatToInts($sum);

        $nf = new \NumberFormatter('lt', \NumberFormatter::SPELLOUT);

        if ($numbers['minorPart'] > 0) {
            return sprintf(
                '%s %s %s %s',
                $nf->format($numbers['mainPart']),
                $translator->transChoice('general.currency_modals', $numbers['mainPart']),
                $nf->format($numbers['minorPart']),
                $translator->transChoice('general.currency_minor_modals', $numbers['minorPart'])
            );
        } else {
            return sprintf(
                '%s %s',
                $nf->format($numbers['mainPart']),
                'euras'
            );
        }
    }

    /**
     * @param $float
     * @return array
     */
    public function floatToInts($float)
    {
        $parts        = explode('.', (string)$float);
        $mainPart = $parts[0];
        if (isset($parts[1])) {
            if ((int)$parts[1] < 10 && strpos($parts[1], '0') !== 0) {
                $parts[1] = $parts[1].'0';
            }
            $minorPart    = ltrim($parts[1], '0');
        } else {
             $minorPart = 0;
        }

        return array(
            'mainPart' => $mainPart,
            'minorPart' => $minorPart,
        );
    }

    /**
     * @param string $name
     * @return string
     * @throws \Exception
     */
    public function getParam($name)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parameter = $em->getRepository('Food\AppBundle\Entity\Param')->findOneBy(array('param' => $name));

        if (!$parameter instanceof Param) {
            throw new \Exception('Parameter not found');
        }

        return $parameter->getValue();
    }

    /**
     * @param string $name
     * @param string|array|int $value
     */
    public function setParam($name, $value)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parameter = $em->getRepository('Food\AppBundle\Entity\Param')->findOneBy(array('param' => $name));

        if (!$parameter instanceof Param) {
            $parameter = new Param();
            $parameter->setParam($name);
        }

        if (is_array($value)) {
            $value = serialize($value);
        }

        $parameter->setValue($value);

        $em->persist($parameter);
        $em->flush();
    }
}