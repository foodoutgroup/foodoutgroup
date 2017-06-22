<?php

namespace Food\BlogBundle\Entity;

use Doctrine\ORM\EntityRepository;

class BlogPostRepository extends EntityRepository
{
    public function getTopThreePostsByCategory(BlogCategory $blogCategory)
    {
        return $this->findBy(['category' => $blogCategory, 'active' => 1], ['createdAt' => 'DESC'], 3);
    }

    public function getAllPostsByCategory(BlogCategory $blogCategory)
    {
        return $this->findBy(['category' => $blogCategory, 'active' => 1], ['createdAt' => 'DESC']);
    }

    public function getBySlug($postSlug)
    {
        return $this->findOneBy(['slug' => $postSlug, 'active' => 1]);
    }
}
