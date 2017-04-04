<?php
namespace Food\AppBundle\Service;


use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\Slug;
use Food\DishesBundle\Utils\Slug\SlugGenerator;
use Food\DishesBundle\Utils\Slug\TextStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use \Food\AppBundle\Entity\Slug as SlugEntity;


class SlugService
{

    private $em;
    private $localeCollection;
    private $defaultLocale;
    private $repository;
    private $request;
    private $router;
    private $container;
    private $misc;

    private $isBanned;

    public function __construct(EntityManager $entityManager, ContainerInterface $container, Router $router, $localeCollection, $defaultLocale)
    {
        $this->em = $entityManager;
        $this->request = $container->get('request');
        $this->container = $container;
        $this->router = $router;
        $this->localeCollection = $localeCollection;
        $this->defaultLocale = $defaultLocale;
        $this->repository = $this->em->getRepository('FoodAppBundle:Slug');
        $this->misc = $this->container->get('food.app.utils.misc');

        $this->isBanned = $this->misc->isIpBanned($this->container->get('request')->getClientIp());
    }

    public function generate($object, $slugField = 'slug', $type = SlugEntity::TYPE_PAGE)
    {

        if (!in_array($type, SlugEntity::$typeCollection)) {
            throw new \Exception('Slug type was not found');
        }

        if (!method_exists($object, 'getTranslations')) {
            throw new \Exception('getTranslations method required');
        }

        $locales = $this->container->getParameter('available_locales');
        $defaultLocale = $this->container->getParameter('locale');

        $textsForSlugs = [];
        $origName = null;

        $method = 'get' . ucfirst($slugField);
        if (method_exists($object, $method)) {
            $textsForSlugs[$defaultLocale] = $object->{$method}();
            $origName = $textsForSlugs[$defaultLocale];
        }

        foreach ($object->getTranslations()->getValues() as $row) {
            if ($row->getField() == $slugField) {
                $textsForSlugs[$row->getLocale()] = $row->getContent();
            }
        }

        foreach ($locales as $loc) {
            if (!isset($textsForSlugs[$loc])) {
                $textsForSlugs[$loc] = $origName;
            }
        }


        foreach ($locales as $loc) {
            $strategy = new TextStrategy($this->container);
            $strategy->setType($type);
            $context = new SlugGenerator($strategy);
            $context->generate($loc, $object->getId(), $textsForSlugs[$loc]);
        }
    }


    public function exist($slug)
    {
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

    /**
     * @param $slug
     * @param $type
     * @return SlugEntity|object
     */
    public function getObjBySlug($slug, $type)
    {
        return $this->repository->findOneBy(
            array(
                'name' => $slug, 'type' => $type,
                'lang_id' => $this->getLocale(), 'active' => 1
            )
        );


    }


    public function getUrl($itemId, $type, $reqLocale = false)
    {
        $locale = $this->getLocale();
        $slug = 'food_slug';

        $urlSlug = $this->get($itemId, $type);
        $params = ['slug' => $urlSlug];

        $url = 'error';
        if ($locale != $this->defaultLocale) {
            $slug = 'food_slug_lang';
            $params['_locale'] = $locale;
            $url = $locale . "/" . $url;
        }

        preg_match('/^(?!admin|cart|invoice|payments|callcenter|newsletter|ajax|js|routing|monitoring|nagios|banned)([a-z0-9-\__\"„“\.\+]+)([a-z0-9-\/\__\"„“\.\+]+)$/', $urlSlug, $matches);

        if ($urlSlug && (isset($matches[0]) && count($matches[0]))) {
            if ($reqLocale) {
                $url = $urlSlug;
            } else {
                $url = $this->router->generate($slug, $params);
            }
        }

        return $url;
    }

    public function generateURL($route, $route_locale = null, $params = [])
    {

        $locale = $this->getLocale();

        if ($locale != $this->defaultLocale) {
            $params['_locale'] = $locale;
            $route = ($route_locale == null ? $route . '_locale' : $route_locale);
        }

        return $this->router->generate($route, $params);
    }

    public function ajaxURL($route, $params = []) {

        $locale = $this->getLocale();
        $params['_locale'] = $locale;
        return $this->router->generate($route, $params);
    }

    public function generatePath($route, $route_locale = null, $params = [])
    {
        $locale = $this->getLocale();

        if ($locale != $this->defaultLocale) {
            $params['_locale'] = $locale;
            $route = ($route_locale == null ? $route . '_locale' : $route_locale);
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
        return $this->router->generate('food_lang_homepage', $params);

    }

    public function getLocale()
    {
        $locale = $this->request->getLocale();
        if (empty($locale)) {
            $locale = $this->defaultLocale;
        }
        return strtolower($locale);
    }


    public function isBanned() {
        return $this->isBanned;
    }

    public function urlFromParam($name, $type)
    {
        $contentId = $bannedEmailPageId = $this->misc->getParam($name);
        return $this->getUrl($contentId, $type);
    }

    /**
     * @deprecated from 2017-03-29
     */
    public function bannedUrl(){
        $bannedPageId = $this->misc->getParam('page_banned');
        return $this->getUrl($bannedPageId, Slug::TYPE_PAGE);
    }


}
