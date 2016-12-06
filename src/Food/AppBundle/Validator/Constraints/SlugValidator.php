<?php

namespace Food\AppBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * @author Aleksas Janulevicius <aseksas@gmail.com>
 * @api
 */
class SlugValidator extends ConstraintValidator
{

    private $regex = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected $em;
    private $repository;
    private $itemId = 0;
    private $localeCollection = [];

    public function __construct(EntityManager $entityManager, $localeCollection)
    {
        $this->em = $entityManager;
        $this->repository = $entityManager->getRepository('FoodAppBundle:Slug');
        $this->localeCollection = $localeCollection;
        if(method_exists($this->context, 'getRoot') && method_exists($this->context->getRoot(), 'getData')) {
            $this->itemId = $this->context->getRoot()->getData()->getId();
        } else {
            $this->itemId = 0;
        }
    }

    public function validate($value, Constraint $constraint)
    {
        var_dump($this->context->getValue());
        var_dump($this->localeCollection);

        if (preg_match($this->regex, $value, $matches)) {
            /**
             * @var $route Slug
             */
            if ($route = $this->repository->getBySlug($value)) {
                // jei tipai nesutampa arba yra redaguojama ir id nesutampa
                if( ($route->getType() != $constraint->type) || ($this->itemId && $route->getId() != $this->itemId)) {
                    $this->context->addViolation($constraint->message['exist'],[]);
                }
            }
        } else {
            $this->context->addViolation($constraint->message['regex'],[]);
        }

    }

}