<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;
use Food\AppBundle\Service\UploadService;
use Symfony\Component\Security\Core\SecurityContext;
use Food\AppBundle\Filter\PlaceFilter;

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
     * @var PlaceFilter
     */
    private $placeFilter = null;

    /**
     * @var bool
     */
    private $placeFilterEnabled = false;

    /**
     * @param PlaceFilter $filter
     * @return Admin
     */
    public function setPlaceFilter(PlaceFilter $filter){
        $this->placeFilter = $filter;

        return $this;
    }

    /**
     * @param bool $status
     */
    public function setPlaceFilterEnabled($status)
    {
        $this->placeFilterEnabled = $status;
    }

    /**
     * @return bool
     */
    public function isPlaceFilterEnabled()
    {
        return $this->placeFilterEnabled;
    }

    /**
     * @param \Food\UserBundle\Entity\User $user
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
            $this->user = $this->getSecurityContext()->getToken()->getUser();
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
        if (method_exists($object, 'setCreatedAt')) {
            $object->setCreatedAt(new \DateTime("now"));
            $object->setCreatedBy($this->getUser());
        }
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
        // Ne visi enticiai turi deleted reiksme :) kai kurie yra hard deletable :P
        if (method_exists($object, 'getDeletedAt')) {
            $deleted = $object->getDeletedAt();
            if (empty($deleted)) {
                // Log this troll, so we could burn him later
                $object->setEditedAt(new \DateTime("now"));
                $object->setEditedBy($this->getUser());
            }
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
        // Ne visi enticiai turi deleted reiksme :) kai kurie yra hard deletable :P
        if (method_exists($object, 'setDeletedBy')) {
            // Log this troll, so we could burn him later
            $object->setDeletedBy($this->getUser());
            $this->update($object);
        }
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->_container = $container;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
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
            $this->securityContext = $this->getContainer()->get('security.context');
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

    /**
     * @inheritdoc
     */
    public function createQuery($context = 'list')
    {
        $query = parent::createQuery($context);

        // Place Filter for moderator
        if ($context == 'list' && $this->isPlaceFilterEnabled() && !empty($this->placeFilter)) {
            $this->placeFilter->apply($query);
        }

        return $query;
    }

    // TODO - remove this after Sonata update.. This is a fix for error: (An exception has been thrown during the rendering of a template ("Parameter "id" for route)
    public function generateObjectUrl($name, $object, array $parameters = array(), $absolute = false)
    {
//        $parameters['id'] = $this->getUrlsafeIdentifier($object);
        $parameters['id'] = $object->getId();
        return $this->generateUrl($name, $parameters, $absolute);
    }

    /**
     * TODO remove after hackers are gone
     * @return bool
     */
    public function getExportFormats()
    {
        return false;
    }
}