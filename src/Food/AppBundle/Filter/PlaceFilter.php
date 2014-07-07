<?php
namespace Food\AppBundle\Filter;

use Food\UserBundle\Entity\User;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Filtras, skirtas administravime atfiltruoti tik moderatoriui priklausancios vietos patiekalus ir kitus irasus
 * @package Food\AppBundle\Filter
 */
class PlaceFilter
{
    /**
    * @var SecurityContext
    */
    private $securityContext;

    private $placeFieldName = 'place';

    /**
     * @param SecurityContext $securityContext
     * @param string $placeFieldName
     */
    public function __construct(SecurityContext $securityContext, $placeFieldName = null)
    {
        $this->securityContext = $securityContext;
        if ($placeFieldName) {
            $this->placeFieldName = $placeFieldName;
        }
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
        return $this->securityContext;
    }

    /**
     * @return null|string
     */
    public function getPlaceFieldName()
    {
        return $this->placeFieldName;
    }

    public function apply(ProxyQueryInterface $query)
    {
        $token = $this->getSecurityContext()->getToken();
        $user = $token->getUser();

        if ($user) {
            if (!($user instanceof User)) {
                throw new \InvalidArgumentException('User %s is not of type Food\UserBundle\Entity\User', $user);
            }

            // Admins are allowed to view all
            if ($this->getSecurityContext()->isGranted('ROLE_ADMIN')) {
                return;
            }

            $place = $user->getPlace();

            if (!$place) {
                throw new \InvalidArgumentException('User %s is moderator, but has no place set to him!', $user);
            }

            $alias = $query->getRootAlias();

            $paramName = $this->getPlaceFieldName().'Param';

            $query->andWhere(
                $query->expr()->eq(
                    sprintf('%s.%s', $alias, $this->getPlaceFieldName()),
                    ':'.$paramName
                )
            );
            $query->setParameter($paramName, $place->getId());
        }
    }
}