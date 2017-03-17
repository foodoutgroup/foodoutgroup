<?php

namespace Food\BlogBundle\Service;

use Food\AppBundle\Service\BaseService;

class BlogService extends BaseService
{
    public function doSmth()
    {
        $categories = [1,2,3,4];
        return $categories;
    }
}