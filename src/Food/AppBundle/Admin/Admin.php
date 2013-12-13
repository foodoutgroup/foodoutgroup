<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Food\AppBundle\Service\UploadService;
use Symfony\Component\Security\Core\SecurityContext;


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
     * @var mixed
     */
    protected $user = null;

    /**
     * @var SecurityContext
     */
    protected $securityContext = null;

    /**
     * @param mixed $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        if (empty($this->user)) {
            // @codeCoverageIgnoreStart
            $this->user = $this->getSecurityContext()->getToken()->getUser();
            // @codeCoverageIgnoreEnd
        }
        return $this->user;
    }

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
        $object->setCreatedAt(new \DateTime("now"));
        $object->setCreatedBy($this->getUser());
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
        $deleted = $object->getDeletedAt();
        if (empty($deleted)) {
            // Log this troll, so we could burn him later
            $object->setEditedAt(new \DateTime("now"));
            $object->setEditedBy($this->getUser());
        }
    }

    /**
     * @param mixed $object
     * @return mixed|void
     *
     * @codeCoverageIgnore
     */
    public function postRemove($object)
    {
        // Log this troll, so we could burn him later
        $object->setDeletedBy($this->getUser());
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
            // @codeCoverageIgnoreStart
            $this->_container = $this->getConfigurationPool()->getContainer();
            // @codeCoverageIgnoreEnd
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
            // @codeCoverageIgnoreStart
            $this->uploadService = $this->getContainer()->get('food.upload');
            // @codeCoverageIgnoreEnd
        }
        return $this->uploadService;
    }

    /**
     * @param \Food\DishesBundle\Entity\Place $object
     */
    public function saveFile($object)
    {
        $uploadService = $this->getUploadService();
        $basepath = $this->getRequest()->getBasePath();

        $uploadService->setObject($object);
        $uploadService->upload($basepath);
    }

    /**
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    public function setSecurityContext($securityContext)
    {
        $this->securityContext = $securityContext;
    }

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        if (empty($this->securityContext)) {
            // @codeCoverageIgnoreStart
            $this->securityContext = $this->getContainer()->get('security.context');
            // @codeCoverageIgnoreEnd
        }
        return $this->securityContext;
    }

    /**
     * Is user just a place moderator?
     *
     * @return bool
     */
    public function isModerator()
    {
        return (
            !$this->getSecurityContext()->isGranted('ROLE_ADMIN')
            && $this->getSecurityContext()->isGranted('ROLE_MODERATOR')
        );
    }

    /**
     * Is user as powerfull as Terminator? Is he Mister Administrator?
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->getSecurityContext()->isGranted('ROLE_ADMIN');
    }
}