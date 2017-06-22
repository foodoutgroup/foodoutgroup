<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlacePointCities
 *
 * @ORM\Table(name="place_point_cities")
 * @ORM\Entity
 */
class PlacePointCities
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \Food\AppBundle\Entity\City
     *
     * @ORM\ManyToOne(targetEntity="\Food\AppBundle\Entity\City")
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     **/
    private $cityId;


    /**
     * @var \Food\DishesBundle\Entity\PlacePoint
     *
     * @ORM\ManyToOne(targetEntity="\Food\DishesBundle\Entity\PlacePoint")
     * @ORM\JoinColumn(name="place_point_id", referencedColumnName="id")
     **/
    private $placePointId;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cityId
     *
     * @param integer $cityId
     * @return PlacePointCities
     */
    public function setCityId($cityId)
    {
        $this->cityId = $cityId;
    
        return $this;
    }

    /**
     * Get cityId
     *
     * @return integer 
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Set placePointId
     *
     * @param integer $placePointId
     * @return PlacePointCities
     */
    public function setPlacePointId($placePointId)
    {
        $this->placePointId = $placePointId;
    
        return $this;
    }

    /**
     * Get placePointId
     *
     * @return integer 
     */
    public function getPlacePointId()
    {
        return $this->placePointId;
    }
}