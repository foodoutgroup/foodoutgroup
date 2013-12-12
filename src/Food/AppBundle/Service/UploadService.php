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
        $getter = $this->getUploadableFieldGetter();

        if (null === $this->object->getFile()) {
            return;
        }

        if (null === $basepath) {
            return;
        }

        // TODO jei yra senas failas - ji trinam
        $filename = $this->generateFileName();
        $uploadDir = $this->getUploadRootDir($basepath);

        $rootPath = $this->container->get('kernel')->getRootDir();

        // Seno failo sutvarkymo flow
        $oldFileName = $this->object->$getter();
        $oldFile = $rootPath.'/../web/'.$uploadDir.'/'.$oldFileName;

        if (!empty($oldFileName) && file_exists($oldFile)) {
            unlink($oldFile);
        }

        // Naujo failo sutvarkymo flow
        $this->object->getFile()->move($uploadDir, $filename);
        $this->object->$setter($filename);
        $this->object->setFile(null);
    }

    /**
     * @return string
     */
    public function generateFileName()
    {
        return sprintf(
            '%d_%s.%s',
            $this->object->getId(),
            md5($this->object->getFile()->getClientOriginalName()),
            $this->object->getFile()->guessClientExtension()
        );
    }
}