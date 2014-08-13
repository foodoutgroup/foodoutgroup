<?php
namespace Food\AppBundle\Service;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;

class UploadService
{

    /**
     * @var \Food\AppBundle\Entity\Uploadable
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
     * @var Imagine
     */
    private $imagine;

    /**
     * @param Container $container
     * @param integer $userId
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
     * @param \Imagine\Gd\Imagine $imagine
     */
    public function setImagine($imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * @return \Imagine\Gd\Imagine
     */
    public function getImagine()
    {
        if (empty($this->imagine)) {
            $this->imagine = new Imagine();
        }
        return $this->imagine;
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
     * @param string|null $basepath
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

        if ($this->object->getMultipleThumbs()) {
            foreach ($this->object->getBoxSize() as $boxKey=>$boxSz) {
                $this->saveThumb($uploadDir, $filename, $boxKey.'_'.$filename, $boxSz['w'], $boxSz['h'], $this->object->getResizeMode());
            }
        } else {
            $boxSize = $this->object->getBoxSize();
            if ($boxSize == null && $this->object->getResizeMode() == null) {
                $this->saveThumb($uploadDir, $filename, $filename);
            } else {
                $this->saveThumb($uploadDir, $filename, $filename, $boxSize['w'], $boxSize['h'], $this->object->getResizeMode());
            }
        }
    }

    /**
     * @param string $uploadDir
     * @param string $origName
     * @param string $newName
     * @param integer $w
     * @param integer $h
     * @param string $mode
     */
    private function saveThumb($uploadDir, $origName, $newName, $w = null, $h = null, $mode = null)
    {
        $imagine = $this->getImagine();
        if($w == null && $h == null && $mode == null) {
            $imagine->open($uploadDir."/".$origName)
                ->save($uploadDir."/thumb_".$newName);
        } else {
            $size = new Box($w, $h);
            $imagine->open($uploadDir."/".$origName)
                ->thumbnail($size, $mode)
                ->save($uploadDir."/thumb_".$newName);
        }
    }

    /**
     * @return string
     */
    public function generateFileName()
    {
        $id = $this->object->getId();

        // If it is a new object and has no ID - generate a more unique one
        if (empty($id) || $id == 0) {
            $id = 'noid_'.date("ymdhis");
        }

        return sprintf(
            '%s_%s.%s',
            $id,
            md5($this->object->getFile()->getClientOriginalName()),
            $this->object->getFile()->guessClientExtension()
        );
    }

    /**
     * Resize an image, return its contents and mime type
     *
     * @param string $file
     * @param int $width
     * @param bool $box
     * @return string
     * @throws \Exception
     */
    public function resizePhoto($file, $width, $box=false)
    {
        if (empty($file)) {
            throw new \Exception('File can not be empty');
        }
        if (empty($width)) {
            throw new \Exception('I can not resize ir width is not specified');
        }

        $rootPath = $this->container->get('kernel')->getRootDir();
        $webPath = $rootPath.'/../web';

        $newName = $this->getMobileImageName($file, $width, $box);

        $imagine = $this->getImagine();
        $imageObject = $imagine->open($webPath.$file);

        if (!$box) {
            $resizedImage = $imageObject->resize(
                $imageObject->getSize()->widen($width)
            );
        } else {
            $size = new Box($width, $width);

            $resizedImage = $imageObject->thumbnail(
                $size,
                ImageInterface::THUMBNAIL_OUTBOUND
            );
        }

        $resizedImage->save($webPath.$newName);

        return $newName;
    }

    /**
     * @param string $file
     * @param int $width
     * @param bool $box
     * @return string
     */
    public function getMobileImageName($file, $width, $box)
    {
        $filename = basename($file);
        $pathInfo = pathinfo($file);
        if (!empty($pathInfo['dirname']) && $pathInfo['dirname'] != '.') {
            $thePath = $pathInfo['dirname'].'/';
        } else {
            $thePath = '';
        }

        $newName = sprintf(
            'mobile_%d_%s_%s',
            $width,
            ($box ? 'box' : 'aspect'),
            $filename
        );

        return $thePath.$newName;
    }
}