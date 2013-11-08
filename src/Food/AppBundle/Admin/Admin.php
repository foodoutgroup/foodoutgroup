<?php
namespace Food\AppBundle\Admin;

use Sonata\AdminBundle\Admin\Admin as SonataAdmin;


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
        $object->setCreatedBy($user->getId());
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
            $object->setEditedBy($user->getId());
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
        // TODO - tures buti userio entitis, kai susitvarkysime useriu teises!
        $object->setDeletedBy($user->getId());
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
}