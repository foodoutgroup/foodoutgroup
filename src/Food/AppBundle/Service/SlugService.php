<?php
namespace Food\AppBundle\Service;

use Aws\CloudFront\Exception\Exception;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

class SlugService {

    private $em;
    private $localeCollection;
    private $defaultLocale;
    private $repository;
    private $request;
    private $router;

    public function __construct(EntityManager $entityManager, ContainerInterface $container,  Router $router, $localeCollection, $defaultLocale)
    {
        $this->em = $entityManager;
        $this->request = $container->get('request');
        $this->router = $router;
        $this->localeCollection = $localeCollection;
        $this->defaultLocale = $defaultLocale;
        $this->repository = $this->em->getRepository('FoodAppBundle:Slug');
    }

    public function generate($object) {

        if(method_exists($object, 'getTranslations')) {
            $textForSlug = [];
            $dataCollection = $object->getTranslations();

            if(method_exists($object, 'getSlug')) {
                $textForSlug[$this->defaultLocale] = $object->getSlug();
            }

            var_dump(count($dataCollection));

            var_dump($dataCollection);

            if ($dataCollection) {
                foreach ($dataCollection as $item) {

                    echo "==============================";
//                    if($item->getField() == 'slug') {
//                        var_dump($item->getContent());
//                    }
//
//                    if (method_exists($item, 'getSlug')) {
//                        $textForSlug[$item->getLocale()] = $item->getSlug();
//                    }
                }
            }

            var_dump($textForSlug);
            die;

//            $textForSlug = [];
//            foreach ($this->localeCollection as $locale) {
//                $textForSlug[$locale] = $slug;
//            }

        }
    }

    public function exist($slug) {
        return $this->repository->exist($slug);
    }

    public function get($itemId, $type)
    {
        $item = $this->repository->findOneBy(
            array(
                'item_id' => $itemId, 'type' => $type,
                'lang_id' => $this->getLocale(), 'active' => 1
            )
        );

        if ($item) {
            return $item->getName();
        } else {
            return null;
        }
    }

    public function getUrl($itemId, $type)
    {
        $locale = $this->getLocale();
        $slug = 'food_slug';

        $urlSlug = $this->get($itemId, $type);
        $params = ['slug' => $urlSlug];

        $url = 'error';
        if($locale != $this->defaultLocale) {
            $slug = 'food_slug_lang';
            $params['_locale'] = $locale;
            $url = $locale."/".$url;
        }

        preg_match('/^(?!admin|cart|invoice|payments|callcenter|newsletter|ajax|js|routing|monitoring|nagios|banned)([a-z0-9-\__\"„“\.\+]+)([a-z0-9-\/\__\"„“\.\+]+)$/', $urlSlug, $matches);

        if($urlSlug && (isset($matches[0]) && count($matches[0]))) {
            $url = $this->router->generate($slug, $params);
        }
        return $url;
    }

    public function generateURL($route, $route_locale = null, $params = [])
    {

        $locale = $this->getLocale();

        if ($locale != $this->defaultLocale) {
            $params['_locale'] = $locale;
            $route = ($route_locale == null ? $route.'_locale' : $route_locale);
        }

        return $this->router->generate($route, $params);
    }

    public function generatePath($route, $route_locale = null, $params = [])
    {
        $locale = $this->getLocale();

        if ($locale != $this->defaultLocale) {
            $params['_locale'] = $locale;
            $route = ($route_locale == null ? $route.'_locale' : $route_locale);
        }

        return $this->router->generate($route, $params);
    }

    public function toHomepage()
    {
        $locale = $this->getLocale();
        $params = [];
        if ($locale != $this->defaultLocale) {
            $params['_locale'] = $locale;
        }
        return $this->router->generate('food_homepage', $params);

    }

    public function getLocale()
    {
        $locale = $this->request->getLocale();
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }
        return strtolower($locale);
    }


}
