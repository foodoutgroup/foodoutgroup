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
    public $uploadableField = 'file';

    /**
     * @return null|string
     */
    public function getWebPath()
    {
        $getter = 'get'.ucfirst($this->uploadableField);
        return null === $this->$getter() ? null : $this->getUploadDir().'/'.$this->$getter();
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw when displaying uploaded doc/image in the view.
        return 'uploads/products';
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