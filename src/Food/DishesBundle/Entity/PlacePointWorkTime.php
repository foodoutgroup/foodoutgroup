<?php

namespace Food\DishesBundle\Entity;

use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlacePointWorkTime
 *
 * @ORM\Table(name="place_point_work_time",indexes={@Index(name="search_idx", columns={"week_day", "start_hour", "start_min", "end_hour", "end_min"})})
 * @ORM\Entity
 */
class PlacePointWorkTime
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
     * @ORM\ManyToOne(targetEntity="PlacePoint", inversedBy="work_times")
     * @ORM\JoinColumn(name="place_point", referencedColumnName="id")
     *
     * @var PlacePoint
     */
    private $placePoint;

    /**
     * @ORM\Column(name="week_day", type="smallint")
     * @var integer
     */
    private $weekDay;

    /**
     * @ORM\Column(type="smallint")
     * @var integer
     */
    private $start_hour;

    /**
     * @ORM\Column(type="smallint")
     * @var integer
     */
    private $start_min;

    /**
     * @ORM\Column(type="smallint")
     * @var integer
     */
    private $end_hour;

    /**
     * @ORM\Column(type="smallint")
     * @var integer
     */
    private $end_min;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return PlacePointWorkTime
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return PlacePoint
     */
    public function getPlacePoint()
    {
        return $this->placePoint;
    }

    /**
     * @param PlacePoint $placePoint
     *
     * @return PlacePointWorkTime
     */
    public function setPlacePoint(PlacePoint $placePoint)
    {
        $this->placePoint = $placePoint;

        return $this;
    }

    /**
     * @return int
     */
    public function getWeekDay()
    {
        return $this->weekDay;
    }

    /**
     * @param int $weekDay
     *
     * @return PlacePointWorkTime
     */
    public function setWeekDay($weekDay)
    {
        $this->weekDay = $weekDay;

        return $this;
    }

    /**
     * @return int
     */
    public function getStartHour()
    {
        return $this->start_hour;
    }

    /**
     * @param int $start_hour
     *
     * @return PlacePointWorkTime
     */
    public function setStartHour($start_hour)
    {
        $this->start_hour = $start_hour;

        return $this;
    }

    /**
     * @return int
     */
    public function getStartMin()
    {
        return $this->start_min;
    }

    /**
     * @param int $start_min
     *
     * @return PlacePointWorkTime
     */
    public function setStartMin($start_min)
    {
        $this->start_min = $start_min;

        return $this;
    }

    /**
     * @return int
     */
    public function getEndHour()
    {
        return $this->end_hour;
    }

    /**
     * @param int $end_hour
     *
     * @return PlacePointWorkTime
     */
    public function setEndHour($end_hour)
    {
        $this->end_hour = $end_hour;

        return $this;
    }

    /**
     * @return int
     */
    public function getEndMin()
    {
        return $this->end_min;
    }

    /**
     * @param int $end_min
     *
     * @return PlacePointWorkTime
     */
    public function setEndMin($end_min)
    {
        $this->end_min = $end_min;

        return $this;
    }

}