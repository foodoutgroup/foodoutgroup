<?php
namespace Food\AppBundle\Controller\Admin;


use Food\DishesBundle\Entity\Place;
use Food\OrderBundle\Entity\OrderDataImport;
use Sonata\AdminBundle\Controller\CoreController;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class TranslationsController extends CoreController
{

    protected $baseRouteName = 'lexik_translation_overview';
    protected $baseRoutePattern = 'translations';
    /**
     * {@inheritdoc}
     */
    public function configureRoutes(RouteCollection $collection)
    {

        $collection->add('list', 'list', [
            '_controller' => 'LexikTranslationBundle:Translation:overview',
        ]);
        $collection->add('grid', 'grid', [
            '_controller' => 'LexikTranslationBundle:Translation:grid',
        ]);
        $collection->add('new', 'new', [
            '_controller' => 'LexikTranslationBundle:Translation:new',
        ]);
    }


}