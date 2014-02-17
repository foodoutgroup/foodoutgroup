<?php
namespace Food\AppBundle\Entity;

/**
 * Class Uploadable
 * @package Food\AppBundle\Entity
 */
class Uploadable {
    /**
     * Name of uploadale database field
     *
     * @var string
     */
    protected $uploadableField = null;

    /**
     * @var string
     */
    protected $uploadDir = null;

    public $multipleThumbs = false;


    public function getImageSetted()
    {
        $getter = 'get'.ucfirst($this->getUploadableField());
        return $this->$getter();
    }
    /**
     * @return null|string
     */
    public function getWebPath()
    {
        return null === $this->$getter() ? null : $this->getUploadDir().'/'.$this->getImageSetted();
    }

    /**
     * @return null|string
     */
    public function getWebPathThumb($type = "")
    {
        $getter = 'get'.ucfirst($this->getUploadableField());
        return null === $this->$getter() ? null : $this->getUploadDir().'/thumb_'.($type!="" ? $type."_" : "") .$this->getImageSetted();
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }

    /**
     * @param $uploadDir
     */
    public function setUploadDir($uploadDir)
    {
        $this->uploadDir = $uploadDir;
    }

    /**
     * @param string $uploadableField
     */
    public function setUploadableField($uploadableField)
    {
        $this->uploadableField = $uploadableField;
    }

    /**
     * @return string
     */
    public function getUploadableField()
    {
        return $this->uploadableField;
    }

    /**
     * @param boolean $multipleThumbs
     */
    public function setMultipleThumbs($multipleThumbs)
    {
        $this->multipleThumbs = $multipleThumbs;
    }

    /**
     * @return boolean
     */
    public function getMultipleThumbs()
    {
        return $this->multipleThumbs;
    }
}