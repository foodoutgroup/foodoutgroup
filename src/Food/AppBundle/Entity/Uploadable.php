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

    protected $multipleThumbs = false;
    protected $boxSize = null;
    protected $resizeMode = null;

    /**
     * @return string
     */
    public function getBoxSize()
    {
        return $this->boxSize;
    }

    /**
     * @return string
     */
    public function getResizeMode()
    {
        return $this->resizeMode;
    }


    /**
     * @return string
     */
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
        $image  = $this->getImageSetted();
        // If no image is set - dont return just the path You little bastard!
        if (empty($image)) {
            return null;
        }

        return null === $image ? null : $this->getUploadDir().'/'.$image;
    }

    /**
     * @param string $type
     * @return null|string
     */
    public function getWebPathThumb($type = "")
    {
        $image  = $this->getImageSetted();
        // If no image is set - dont return just the path You little bastard!
        if (empty($image)) {
            return null;
        }

        return null === $image ? null : $this->getUploadDir().'/thumb_'.($type!="" ? $type."_" : "") .$image;
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        return $this->uploadDir;
    }

    /**
     * @param string $uploadDir
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