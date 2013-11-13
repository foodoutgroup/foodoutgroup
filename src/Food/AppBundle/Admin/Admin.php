<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Food\AppBundle\Service\UploadService;


/**
 * Class FooAdmin
 * @package Food\AppBundle\FooAdmin
 */
class Admin extends SonataAdmin
{
    /**
     * @var null|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $_container = null;

    /**
     * @var UploadService
     */
    protected $uploadService = null;

    /**
     * Set create date before inserting to database
     *
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return mixed|void
     */
    public function prePersist($object)
    {
        // The magic container is here
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $object->setCreatedAt(new \DateTime("now"));
        $object->setCreatedBy($user);
    }

    /**
     * Set editing time before inserting to database
     * @inheritdoc
     *
     * @param \Food\DishesBundle\Entity\Dish $object
     * @return mixed|void
     */
    public function preUpdate($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();

        $deleted = $object->getDeletedAt();
        if (empty($deleted)) {
            // Log this troll, so we could burn him later
            $object->setEditedAt(new \DateTime("now"));
            $object->setEditedBy($user);
        }
    }

    /**
     * @param mixed $object
     * @return mixed|void
     */
    public function postRemove($object)
    {
        $securityContext = $this->getContainer()->get('security.context');
        $user = $securityContext->getToken()->getUser();

        // Log this troll, so we could burn him later
        $object->setDeletedBy($user);
        $this->update($object);
    }

    /**
     * @param null|\Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->_container = $container;
    }

    /**
     * @return null|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        if (empty($this->_container)) {
            $this->_container = $this->getConfigurationPool()->getContainer();
        }
        return $this->_container;
    }

    /**
     * @param \Food\AppBundle\Service\UploadService $uploadService
     */
    public function setUploadService($uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * @return \Food\AppBundle\Service\UploadService
     */
    public function getUploadService()
    {
        if (empty($this->uploadService)) {
            $this->uploadService = $this->getContainer()->get('food.upload');
        }
        return $this->uploadService;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function saveFile($object) {
        $uploadService = $this->getUploadService();
        $basepath = $this->getRequest()->getBasePath();

        $uploadService->setObject($object);
        $uploadService->upload($basepath);
    }
}