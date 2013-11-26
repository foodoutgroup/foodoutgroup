<?php

namespace Food\AppBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Gedmo\Translator\Entity\Translation;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $product = $this->getDoctrine()
            ->getRepository('FoodDishesBundle:Kitchen')
            ->find(2)
;
        $em = $this->getDoctrine()->getManager();

        //$article = $em->find('FoodDishesBundle:Kitchen', 2 /*article id*/);
        $em = $this->getDoctrine()->getEntityManager();

                //$repository = $em->getRepository(Gedmo\Translator\Entity\Translation);
        //$translations = $repository->findTranslations($article);
        //var_dump($translations);
        var_dump($product->getName());
        var_dump($product->getOrigName($em));
        die();

        echo "<pre>";
        var_dump($product->getName());
        var_dump($product->getTranslations()[0]);

        foreach( $product->getTranslations()->getValues() as $row) {
            echo "---\n";
            var_dump($row->getContent());
        }


        die();
        return $this->render('FoodAppBundle:Default:index.html.twig');
    }
}
