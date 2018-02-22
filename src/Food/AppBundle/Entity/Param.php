<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Food\AppBundle\Entity\Uploadable;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Food\AppBundle\Entity\Param
 *
 * @ORM\Table(name="params")
 * @ORM\Entity
 */
class Param extends Uploadable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="param", type="string", length=64, unique=true)
     */
    private $param;

    /**
     * @var string
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @var \Food\AppBundle\Entity\ParamLog $paramLog
     * @ORM\OneToMany(targetEntity="\Food\AppBundle\Entity\ParamLog", mappedBy="param")
     **/
    private $paramLog;

    /**
     * @var object
     */
    protected $file;

    protected $resizeMode = null;
    protected $boxSize = null;

    /**
     * @var string
     *
     * @ORM\Column(name="photo", type="string", length=255)
     */
    private $photo = "";

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getId()) {
            return $this->getId().'-'.$this->getParam();
        }

        return '';
    }


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
     * Set param
     *
     * @param string $param
     * @return Param
     */
    public function setParam($param)
    {
        $this->param = $param;
    
        return $this;
    }

    /**
     * Get param
     *
     * @return string 
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Param
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set version
     *
     * @param integer $version
     * @return Param
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return integer 
     */
    public function getVersion()
    {
        return $this->version;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paramLog = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add paramLog
     *
     * @param \Food\AppBundle\Entity\ParamLog $paramLog
     * @return Param
     */
    public function addParamLog(\Food\AppBundle\Entity\ParamLog $paramLog)
    {
        $this->paramLog[] = $paramLog;
    
        return $this;
    }

    /**
     * Remove paramLog
     *
     * @param \Food\AppBundle\Entity\ParamLog $paramLog
     */
    public function removeParamLog(\Food\AppBundle\Entity\ParamLog $paramLog)
    {
        $this->paramLog->removeElement($paramLog);
    }

    /**
     * Get paramLog
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getParamLog()
    {
        return $this->paramLog;
    }

    /**
     * Set photo
     *
     * @param string $photo
     * @return Param
     */
    public function setPhoto($photo)
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * Get photo
     *
     * @return string
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @return string
     */
    public function getUploadableField()
    {
        return 'photo';
    }

    /**
     * @return string
     */
    public function getUploadDir()
    {
        if (empty($this->uploadDir)) {
            $this->uploadDir = 'uploads/covers';
        }
        return $this->uploadDir;
    }

    /**
     * @param object $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return object
     */
    public function getFile()
    {
        return $this->file;
    }
}
