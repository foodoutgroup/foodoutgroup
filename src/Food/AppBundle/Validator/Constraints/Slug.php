<?php

namespace Food\AppBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 * @author Aleksas Janulevicius <aseksas@gmail.com>
 * @api
 */
class Slug extends Constraint
{
    public $message = ['regex' => 'Bad address structure', 'exist' => 'This address already exists'];
    public $type;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'type';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return ['type'];
    }

    public function validatedBy()
    {
        return 'validate.slug';
    }


}