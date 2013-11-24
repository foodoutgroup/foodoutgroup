<?php

namespace Food\AppBundle\Controller;



use Doctrine\Common\Inflector\Inflector;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class TestController extends Controller
{
    public function indexAction()
    {
        //return $this->render('FoodAppBundle:Default:index.html.twig');

        $languages = $this->get('food.app.utils.language')->getAll();
        $omg = $this->get('food.dishes.utils.slug');
        //$omg->generateForTexts('lt');
       //$newSlug = $omg->generateForTexts('lt', 1, 'Piciška šėtoniška piciutė');
        $newSlug = $omg->generateForTexts('lt', 2, 'ketvirtas jo');


        //$newSlug = $omg->generateForTexts('ru', 1, 'холодильник');

        //$newSlug->

        /**
         * @todo
         *
         * Vienas tipas vienas viena strategija
         * Generuojama single line vienu kvietimu.
         * Dishes - slugas - "cili-pizza/pizza-mit-ravioli"
         * Kiekvienai kalbai kiti slugai :)
         */


        //$languages = $this->getConfigurationPool()->getContainer()->get('food.app.utils.language');

        /*
        foreach ($languages as $language) {
            $container->get('fish.parado.utils.slug')->generateForBrands($language->getId());
            $container->get('fish.parado.utils.slug')->generateForCategories($language->getId());
            $container->get('fish.parado.utils.slug')->generateForProducts($language->getId());
            $container->get('fish.parado.utils.slug')->generateForTexts($language->getId());
        }
        */
        return new Response('Uber');

    }
}