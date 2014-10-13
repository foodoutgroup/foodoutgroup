<?php

namespace Food\OrderBundle\Service\NavService;

class OrderDataForNav
{
    protected $targetEncoding = 'ISO-8859-13';
    protected $sourceEncoding = 'utf-8';
    protected $lengthRestrictions = [
        'staff' => 250,
        'chain' => 100,
        'restaurant' => 100,
        'restaurantAddress' => 100,
        'driver' => 50,
        'deliveryType' => 20,
        'clientName' => 100,
        'delivered' => 20,
        'deliveryAddress' => 100,
        'city' => 20,
        'country' => 20,
        'paymentType' => 20,
        'discountType' => 50
    ];
    protected $dataTypeRestrictions = [
        'id' => 'integer',
        'date' => 'string',
        'time' => 'string',
        'deliveryDate' => 'string',
        'deliveryTime' => 'string',
        'staff' => 'string',
        'chain' => 'string',
        'restaurant' => 'string',
        'restaurantAddress' => 'string',
        'driver' => 'string',
        'deliveryType' => 'string',
        'clientName' => 'string',
        'isDelivered' => 'string',
        'deliveryAddress' => 'string',
        'city' => 'string',
        'country' => 'string',
        'paymentType' => 'string',
        'foodAmount' => 'double',
        'foodAmountEUR' => 'double',
        'foodVAT' => 'double',
        'drinksAmount' => 'double',
        'drinksAmountEUR' => 'double',
        'drinksVAT' => 'double',
        'alcoholAmount' => 'double',
        'alcoholAmountEUR' => 'double',
        'alcoholVAT' => 'double',
        'deliveryAmount' => 'double',
        'deliveryAmountEUR' => 'double',
        'deliveryVAT' => 'double',
        'giftCardAmount' => 'double',
        'giftCardAmountEUR' => 'double',
        'discountType' => 'string',
        'discountAmount' => 'double',
        'discountAmountEUR' => 'double',
        'discountPercent' => 'double',
        'totalAmount' => 'double',
        'totalAmountEUR' => 'double'
    ];

    protected $id;
    protected $date;
    protected $time;
    protected $deliveryDate;
    protected $deliveryTime;
    protected $staff;
    protected $chain;
    protected $restaurant;
    protected $restaurantAddress;
    protected $driver;
    protected $deliveryType;
    protected $clientName;
    protected $isDelivered;
    protected $deliveryAddress;
    protected $city;
    protected $country;
    protected $paymentType;
    protected $foodAmount;
    protected $foodAmountEUR;
    protected $foodVAT;
    protected $drinksAmount;
    protected $drinksAmountEUR;
    protected $drinksVAT;
    protected $alcoholAmount;
    protected $alcoholAmountEUR;
    protected $alcoholVAT;
    protected $deliveryAmount;
    protected $deliveryAmountEUR;
    protected $deliveryVAT;
    protected $giftCardAmount;
    protected $giftCardAmountEUR;
    protected $discountType;
    protected $discountAmount;
    protected $discountAmountEUR;
    protected $discountPercent;
    protected $totalAmount;
    protected $totalAmountEUR;

    public function __set($property, $value)
    {
        if (!property_exists($this, $property)) {
            throw new \InvalidArgumentException("'$property' does not exist.");
        }

        $valueType = gettype($value);
        $expectedType = $this->dataTypeRestrictions[$property];

        if ($valueType !== $expectedType) {
            throw new \InvalidArgumentException(
                "'$property' is not of type '$expectedType'");
        }

        $this->$property = $value;
        return $this;
    }

    /**
     * Wonder why this magic method works? Well because our properties
     * are inaccessible from outside (they are protected).
     * @param  string $property
     * @return string|double
     */
    public function __get($property)
    {
        if (!property_exists($this, $property)) {
            throw new \InvalidArgumentException("'$property' does not exist.");
        }

        return $this->imposeLengthRestrictions(
            $property,
            $this->encode($this->{$property}));
    }

    protected function imposeLengthRestrictions($property, $value)
    {
        if (isset($this->lengthRestrictions[$property])) {
            return iconv_substr(
                $value,
                0,
                $this->lengthRestrictions[$property],
                $this->targetEncoding);
        }

        return $value;
    }

    protected function encode($value)
    {
        return iconv($this->sourceEncoding, $this->targetEncoding, $value);
    }
}
