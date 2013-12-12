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

    /**
     * @return null|string
     */
    public function getWebPath()
    {
        $getter = 'get'.ucfirst($this->getUploadableField());
        return null === $this->$getter() ? null : $this->getUploadDir().'/'.$this->$getter();
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
}