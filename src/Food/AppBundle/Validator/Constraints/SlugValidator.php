<?php

namespace Food\AppBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * @author Aleksas Janulevicius <aseksas@gmail.com>
 * @api
 */
class SlugValidator extends ConstraintValidator
{

    private $regex = '/^(?!admin|cart|login|logout|invoice|payments|newsletter|ajax|js|routing|banned)([\pL0-9-\/\__\"„“\.\+]+)/u';

    protected $em;
    private $repository;
    private $itemId = 0;
    private $localeCollection = [];
    private $defaultLocale;

    public function __construct(EntityManager $entityManager, $localeCollection, $defaultLocale, $regex)
    {
        $this->defaultLocale = $defaultLocale;
        $this->em = $entityManager;
        $this->repository = $entityManager->getRepository('FoodAppBundle:Slug');
        $this->localeCollection = $localeCollection;
////        $this->regex = "/".$regex."/u";
////        var_dump($this->regex);
//        die;
        if(method_exists($this->context, 'getRoot') && method_exists($this->context->getRoot(), 'getData')) {
            $this->itemId = $this->context->getRoot()->getData()->getId();
        } else {
            $this->itemId = 0;
        }
    }

    public function validate($value, Constraint $constraint)
    {


        if (preg_match($this->regex, $value, $matches)) {



            /**
             * @var $route Slug
             */
            $locale = $this->getLocale();



            $route = $this->repository->getBySlugAndLocale($value, $locale);

            if($route) {
                if( ($route->getType() != $constraint->type) || ($this->itemId && $route->getId() != $this->itemId)) {
                    $this->context->addViolation($constraint->message['exist'],[]);
                }
            }

        } else {
            $this->context->addViolation($constraint->message['regex'],[]);
        }

    }

    private function getLocale(){

        foreach ($this->localeCollection as $locale) {
            if(preg_match('/children\['.$locale.'\]/', $this->context->getPropertyPath())){
                return $locale;
            }
        }
        return $this->defaultLocale;
    }

}