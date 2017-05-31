<?php

namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\ErrorLog;
use Food\AppBundle\Entity\PhoneCodes;
use Symfony\Component\DependencyInjection\ContainerAware;

class RestCountryService extends BaseService
{
    public function getUploadAll()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://restcountries.eu/rest/v2/all');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json')); // Assuming you're requesting JSON
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $countries = json_decode(curl_exec($ch));

        foreach ($countries as $country) {
            if (!empty($country->callingCodes[0]) && !empty($country->alpha2Code)) {
                $countryData = new PhoneCodes();
                $countryData->setCode($country->callingCodes[0]);
                $countryData->setCountry($country->alpha2Code);
                $countryData->setActive(true);
                $this->em->persist($countryData);
                $this->em->flush($countryData);
            }
        }
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function getActiveDropdown()
    {
        $allCodes = $this->em->getRepository('FoodAppBundle:PhoneCodes')->getActive();

        foreach ($allCodes as $code) {
            $result[$code->getCountry()] = $code->getCode();
        }

        return $result;
    }

    public function getByCountry($country)
    {
        return $this->em->getRepository('FoodAppBundle:PhoneCodes')->findBy(array('active' => 1, 'country' => $country));
    }

    public function validatePhoneNumber($phone, $country)
    {

        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        $phoneCode = $this->getByCountry($country);
        $phone = $phoneCode[0]->getCode() . $phone;

        try {
            $numberProto = $phoneUtil->parse($phone, $country);

        } catch (\libphonenumber\NumberParseException $e) {
            // no need for exception
        }

        if (isset($numberProto)) {
            $numberType = $phoneUtil->getNumberType($numberProto);
            $isValid = $phoneUtil->isValidNumber($numberProto);

        } else {
            $isValid = false;
        }

        if (!$isValid) {
            $formErrors[] = 'order.form.errors.customerphone_format';

        } else if ($isValid && !in_array($numberType, [\libphonenumber\PhoneNumberType::MOBILE, \libphonenumber\PhoneNumberType::FIXED_LINE_OR_MOBILE])) {
            $formErrors[] = 'order.form.errors.customerphone_not_mobile';
        } else {
            $phonePass = true;
        }

        if (!empty($formErrors)) {

            return $formErrors;
        } else {

            return $phonePass;
        }
    }

    public function changePhoneFormat($user)
    {
        $phone = '';
        $userPhone = $user->getCountryCode();

        if(!empty($userPhone)) {
            $phoneCode = $this->getByCountry($user->getCountryCode())[0]->getCode();

            $phone = str_replace($phoneCode, '', $user->getPhone());
        }
        return $phone;
    }

    public function getCountryCode($user, $country)
    {

        if (!empty($user)) {
            $userCode = $user->getCountryCode();

            if (empty($userCode)) {
                $userCode = $country;
            }
        }else{
            $userCode = $country;
        }
        return $userCode;
    }
}