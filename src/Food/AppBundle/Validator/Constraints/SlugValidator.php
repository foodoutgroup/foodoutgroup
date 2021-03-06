<?php

namespace Food\AppBundle\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\DependencyInjection\Container;

/**
 * @Annotation
 * @author Aleksas Janulevicius <aseksas@gmail.com>
 * @api
 */
class SlugValidator extends ConstraintValidator
{

    private $regex = '/^(admin|payment|api|js|user|[\/])$/';

    protected $em;
    private $repository;
    private $itemId = 0;
    private $localeCollection = [];
    private $defaultLocale;

    public function __construct(EntityManager $entityManager, $localeCollection, $defaultLocale, Container $container)
    {
        $this->defaultLocale = $defaultLocale;
        $this->em = $entityManager;
        $this->repository = $entityManager->getRepository('FoodAppBundle:Slug');
        $this->localeCollection = $localeCollection;
        if(method_exists($this->context, 'getRoot') && method_exists($this->context->getRoot(), 'getData')) {
            $this->itemId = $this->context->getRoot()->getData()->getId();
        } elseif (null !== $container->get('request')->get('id')) {
            $this->itemId = (int) $container->get('request')->get('id');}
        else {
            $this->itemId = 0;
        }
    }

    public function validate($value, Constraint $constraint)
    {
        if (preg_match($this->regex, $value)) {
            $this->context->addViolation($constraint->message['regex'],[]);
        } else {
            /**
             * @var $route Slug
             */
            $locale = $this->getLocale();
            $route = $this->repository->getBySlugAndLocale($value, $locale);

            if($route) {
                if( !$this->itemId || $route->getItemId() != $this->itemId || $route->getType() != $constraint->type  ) {
                    $this->context->addViolation($constraint->message['exist'],[]);
                }
            } else if(mb_strlen(trim($value)) <= 2) {
                $this->context->addViolation($constraint->message['length'],[]);
            }
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