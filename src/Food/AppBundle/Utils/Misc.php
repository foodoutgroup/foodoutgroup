<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Entity\City;
use Food\AppBundle\Entity\CityLog;
use Food\AppBundle\Entity\Param;
use Food\AppBundle\Traits;
use Food\OrderBundle\Entity\Order;
use Food\UserBundle\Entity\User;
use Food\AppBundle\Entity\ParamLog;

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
     *
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

        if (in_array($numberType, [\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE])) {
            return true;
        }

        return false;
    }

    /**
     * @param string $ip
     *
     * @return bool
     */
    public function isIpBanned($ip)
    {
        $repository = $this->container->get('doctrine')
            ->getRepository('FoodAppBundle:BannedIp')
        ;
        $isBanned = $repository->findOneBy(['ip' => $ip, 'active' => true]);

        if ($isBanned) {
            return true;
        }

        return false;
    }

    /**
     * Parses address string to street, house, flat numbers
     *
     * @param string $address
     *
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

        return [
            'street' => $street,
            'house'  => $house,
            'flat'   => $flat,
        ];
    }

    /**
     * @param float $price
     *
     * @return float
     */
    public function getEuro($price)
    {
        $euroPrice = $price / $this->accountingEuroRate;

        return round($euroPrice, 2);
    }

    /**
     * @param float $price
     *
     * @return float
     */
    public function getLitas($price)
    {
        $litasPrice = $price * $this->accountingEuroRate;

        return round($litasPrice, 2);
    }

    /**
     * @param int|float $sum
     *
     * @return string
     */
    public function priceToText($sum)
    {
        $translator = $this->getContainer()->get('translator');

        $numbers = $this->floatToInts($sum);

        $nf = new \NumberFormatter($this->getContainer()->getParameter('locale'), \NumberFormatter::SPELLOUT);

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
     *
     * @return array
     */
    public function floatToInts($float)
    {
        $parts = explode('.', (string)$float);
        $mainPart = $parts[0];
        if (isset($parts[1])) {
            if ((int)$parts[1] < 10 && strpos($parts[1], '0') !== 0) {
                $parts[1] = $parts[1] . '0';
            }
            $minorPart = ltrim($parts[1], '0');
        } else {
            $minorPart = 0;
        }

        return [
            'mainPart'  => $mainPart,
            'minorPart' => $minorPart,
        ];
    }

    /**
     * @param string  $name
     * @param boolean $noException
     *
     * @return string
     * @throws \Exception
     */
    public function getParam($name, $noException = false)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parameter = $em->getRepository('Food\AppBundle\Entity\Param')->findOneBy(['param' => $name]);

        if ($noException && !$parameter instanceof Param) {
            return null;
        }

        if (!$parameter instanceof Param) {
            throw new \Exception('Parameter "' . $name . '" not found');
        }

        return $parameter->getValue();
    }

    /**
     * @param string           $name
     * @param string|array|int $value
     */
    public function setParam($name, $value)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $parameter = $em->getRepository('Food\AppBundle\Entity\Param')->findOneBy(['param' => $name]);

        if (!$parameter instanceof Param) {
            $parameter = new Param();
            $parameter->setParam($name);
        }

        if (is_array($value)) {
            $value = serialize($value);
        }

        $parameter->setValue($value);
        $this->logParamChange($parameter, $value);

        $em->persist($parameter);
        $em->flush();
    }

    /**
     * @param Param $param
     * @param       $oldValue
     */
    public function logParamChange($param = null, $oldValue)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        if ($this->getContainer()->get('security.context')->getToken()) {
            $user = $this->getContainer()->get('security.context')->getToken()->getUser();

            $log = new ParamLog();
            $log->setParam($param)
                ->setEventDate(new \DateTime('now'))
                ->setOldValue($oldValue)
                ->setNewValue($param->getValue())
            ;

            if ($user instanceof User) {
                $log->setUser($user);
            }

            $em->persist($log);
        }
    }

    /**
     * @param City  $newValue
     * @param City  $oldValue
     */
    public function logCityChange($newValue, $oldValue)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        if ($this->getContainer()->get('security.context')->getToken()) {
            $user = $this->getContainer()->get('security.context')->getToken()->getUser();
            $log = new CityLog();
            $log->setCity($newValue)
                ->setEventDate(new \DateTime('now'))
                ->setOldValue(json_encode($oldValue))
                ->setNewValue($newValue->jsonSerialize())
            ;

            if ($user instanceof User) {
                $log->setUser($user);
            }

            $em->persist($log);
        }
    }

    /**
     * @param Order $order
     */
    public function getDriver(Order $order)
    {
        $query = "SELECT d.name FROM drivers d, orders o WHERE d.id=o.driver_id AND o.id=" . $order->getId();
        $stmt = $this->container->get('doctrine')->getManager()->getConnection()->prepare($query);
        $stmt->execute();
        $driver = $stmt->fetchColumn(0);

        return $driver;
    }

    /**
     * @param User $user
     *
     * @return boolean
     *
     * @throws \InvalidArgumentException
     */
    public function isNewOrSuspectedUser($user)
    {
        if (empty($user) || !$user instanceof User) {
            throw new \InvalidArgumentException('To check if user fraudulent - please, pass me a user');
        }

        $email = $user->getEmail();
        $orderRepo = $this->getContainer()->get('doctrine')->getRepository('FoodOrderBundle:Order');
        $orderService = $this->getContainer()->get('food.order');
        $phone = $user->getPhone();

        $fraudPossible = true;

        // Check if possibly a fraudulent email

        /*
         * Nepraleidzia tokiu:
         *  - a@mail.lt
         *  - petras@a.lt
         *  - jonas@aaa.lt
         */
        if (!preg_match('/[a-zA-Z0-9]{2,}@[a-zA-Z0-9]{4,}\./', $email)) {
            $fraudPossible = true;
        }

        // Check if possibly a fraudulent phone
        if (in_array($phone, ['37060000000', '371'])
            || strpos($phone, '12345')
        ) {
            $fraudPossible = true;
        }

        // Check if there were completed orders from this user
        $userOrder = $orderService->getUserOrders($user);
        if (is_array($userOrder) && count($userOrder) > 0) {
            return false;
        }

        // Check if there were order with this phone
        $phoneOrders = $orderRepo->getCompletedOrdersByPhone($phone);
        if (is_array($phoneOrders) && count($phoneOrders) > 0) {
            return false;
        }

        return $fraudPossible;
    }

    /**
     * @param $user
     * @param $code
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getDivisionName($user, $code)
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('GetDivision name called without user');
        }

        if (empty($code) && $user->getRequiredDivision()) {
            throw new \InvalidArgumentException('GetDivision name called without code');
        }

        $repo = $this->getContainer()->get('doctrine')->getRepository('FoodUserBundle:UserDivisionCode');

        $division = $repo->findOneBy([
            'user' => $user,
            'code' => $code,
        ]);

        if (!$division) {
            return '';
        }

        return $division->getDivision();
    }

    public function stripFaqVideo($text)
    {
        $text = str_replace("{{ faq_video }}", "", $text);
        $text = str_replace("&nbsp; ", "&nbsp;", $text);
        $text = preg_replace('/\<p\>(&nbsp;){2,}\<\/p\>/', "", $text);

        return $text;
    }

    /**
     * Parse from idiotic string like "1.5 val" to minutes
     *
     * @param string $time
     *
     * @return int
     */
    public function parseTimeToMinutes($time)
    {
        $pattern = '/('.implode('|', ['val', $this->container->get('translator')->trans('general.hour')]).')/';

        $digits = preg_replace('/[^\d,.\-]/', '', $time);
        $digits = trim(str_replace(',', '.', $digits));

        if (strpos($digits, '-') !== false) {
            $parts = explode('-', $digits);

            $digits = $parts[1];
        }

        $floatValue = floatval($digits);

        $minutes = (preg_match($pattern, $time)) ? $floatValue * 60 : $floatValue;

        return $minutes;
    }
}
