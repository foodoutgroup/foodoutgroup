<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Traits;

class LocaleService {
    use Traits\Service;

    /**
     * @var EntityManager
     */
    private $entity;

    /**
     * @param $entity
     */
    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function getDefault()
    {
        return "lt";
    }

    public function getAvailable(){
        return ["lt", "en", "ru"];
    }

    public function __call()
    {
        var_dump(func_get_args());
        die;
    }

}
