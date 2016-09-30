<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Food\AppBundle\Utils\Misc;

class ZavalasService extends BaseService
{

    protected $miscService;

    public function __construct(EntityManager $em, Misc $miscService)
    {
        parent::__construct($em);
        $this->miscService = $miscService;
    }

    public function isZavalasTurnedOn()
    {
        $zavalasStatus = false;
        if ($this->miscService->getParam('zaval_on')) {
            $zavalasStatus = true;
        }
        return $zavalasStatus;
    }
}