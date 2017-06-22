<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class HookSitemap {


    private $templating;
    private $em;
    private $container;

    private $params = [];

    public function __construct(EngineInterface $templating, EntityManager $entityManager, ContainerInterface $container)
    {
        $this->templating = $templating;
        $this->em = $entityManager;
        $this->container = $container;
    }

    public function getResponse()
    {
        $r = new Response();
        $r->headers->set('Content-Type', 'xml');
        return $r;
    }

    public function build()
    {
        $this->params = $this->getData();

        return ['template' => '@FoodApp/Hook/sitemap.xml.twig', 'params' => $this->params];
    }

    private function getData()
    {
        $response = [];

        $response['dev'] = false;//($this->container->getParameter('kernel.environment') == "dev");

        $request = $this->container->get('request');

        $placeCollection = $this->em
            ->getRepository('FoodDishesBundle:Place')
            ->findBy(['active' => 1]);

        $response['placeCollection'] = $placeCollection;

        $pageCollection = $this->container->get('food.static')->getActivePages(30, true);
        $response['pageCollection'] = $pageCollection;

        $cityCollection = $this->container->get('food.city_service')->getActiveCity();
        $response['cityCollection'] = $cityCollection;

        $blogCollection = [];

        $blogCategoryCollection = $this->container->get('doctrine')
            ->getRepository('FoodBlogBundle:BlogCategory')
            ->findBy(['active' => 1, 'language' => $request->getLocale()]);

        foreach ($blogCategoryCollection as $category) {

            $blogCollection[] = [
                'category' => $category,
                'postCollection' => $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogPost')
                    ->getAllPostsByCategory($category)
            ];

        }

        $cityKitchenCollection = [];
        foreach ($cityCollection as $city) {
            $cityKitchenCollection[$city->getId()] = $this->container->get('food.places')->getKitchensByCity($city);
        }
        $response['cityKitchenCollection'] = $cityKitchenCollection;
        $response['domain'] = str_replace(["/app_dev.php/"], "", $this->container->getParameter('domain'));
        $response['blogCollection'] = $blogCollection;
        $response['page_block_config'] = $this->container->get('food.app.utils.misc')->getParam('page_blog');

        return $response;

    }
}
