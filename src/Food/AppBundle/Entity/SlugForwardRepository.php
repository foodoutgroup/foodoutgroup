<?php

namespace Food\AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * SlugForwardRepository

 */
class SlugForwardRepository extends EntityRepository
{
    public function getByLocaleAndSlug($locale, $slug)
    {
        return $this->findOneBy(array('locale' => $locale, 'slug' => $slug));

    }
}
