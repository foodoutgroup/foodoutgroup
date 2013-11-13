<?php
namespace Food\AppBundle\Service;

use Doctrine\Tests\Common\Annotations\Ticket\Doctrine\ORM\Mapping\Entity;

class UploadService
{

    /**
     * @var Entity
     */
    protected $object;

    /**
     * @var string
     */
    protected $uploadableFieldSetter = null;

    /**
     * @var string
     */
    protected $uploadableFieldGetter = null;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var int
     */
    private $userId;

    /**
     * @param Container $container
     * @param $userId
     */
    public function __construct($container, $userId)
    {
        $this->container = $container;
        $this->userId = $userId;
    }

    /**
     * @param \Food\AppBundle\Service\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Food\AppBundle\Service\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param \Food\AppBundle\Entity\Uploadable $object
     */
    public function setObject($object)
    {
        $this->object = $object;
    }

    /**
     * @return \Food\AppBundle\Entity\Uploadable
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getUploadableField()
    {
        if (empty($this->object)) {
            throw new \InvalidArgumentException('No object is set You lazy programmer!');
        }
        return $this->object->getUploadableField();
    }

    /**
     * @param string $uploadableFieldGetter
     */
    public function setUploadableFieldGetter($uploadableFieldGetter)
    {
        $this->uploadableFieldGetter = $uploadableFieldGetter;
    }

    /**
     * @return string
     */
    public function getUploadableFieldGetter()
    {
        if (empty($this->uploadableFieldGetter)) {
            $this->uploadableFieldGetter = 'get'.ucfirst($this->getUploadableField());
        }
        return $this->uploadableFieldGetter;
    }

    /**
     * @param string $uploadableFieldSetter
     */
    public function setUploadableFieldSetter($uploadableFieldSetter)
    {
        $this->uploadableFieldSetter = $uploadableFieldSetter;
    }

    /**
     * @return string
     */
    public function getUploadableFieldSetter()
    {
        if (empty($this->uploadableFieldSetter)) {
            $this->uploadableFieldSetter = 'set'.ucfirst($this->getUploadableField());
        }
        return $this->uploadableFieldSetter;
    }

    /**
     * @return null|string
     */
    public function getAbsolutePath()
    {
        $getter = $this->getUploadableFieldGetter();
        return null === $this->object->$getter() ? null : $this->getUploadRootDir().'/'.$this->object->$getter();
    }

    /**
     * @param $basepath
     * @return string
     */
    protected function getUploadRootDir($basepath ='')
    {
        return $basepath.$this->object->getUploadDir();
    }

    /**
     * @todo Sukurti unikalaus filename generavimo funkcionaluma.
     *
     * @param $basepath
     */
    public function upload($basepath)
    {
        $setter = $this->getUploadableFieldSetter();

        if (null === $this->object->getFile()) {
            return;
        }

        if (null === $basepath) {
            return;
        }

        $this->object->getFile()->move($this->getUploadRootDir($basepath), $this->object->getFile()->getClientOriginalName());

        $this->object->$setter($this->object->getFile()->getClientOriginalName());

        $this->object->setFile(null);
    }
}