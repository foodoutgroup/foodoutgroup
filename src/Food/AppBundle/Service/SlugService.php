<?php
namespace Food\AppBundle\Service;


use Aws\CloudFront\Exception\Exception;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\Entity;
use Doctrine\ORM\EntityManager;
use Food\AppBundle\Entity\Slug;
use Food\AppBundle\Entity\City;
use Food\DishesBundle\Utils\Slug\SlugGenerator;
use Food\DishesBundle\Utils\Slug\TextStrategy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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


    public static $cache = [];
    public static $cachePath = [];

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

    public function generateForLocale($locale, $object,  $slugField = 'slug', $type = SlugEntity::TYPE_PAGE)
    {
        $defined = false;
        if(defined(get_class($object) . '::SLUG_TYPE')){
            $defined = true;
            $type = $object::SLUG_TYPE;
        } elseif ($type === null)
        {
            throw new \Exception('Type not defined');
        }


        if ($type != null && !$defined && !in_array($type, SlugEntity::$typeCollection)) {
            throw new \Exception('Slug type was not found');
        }

        if (!method_exists($object, 'getTranslations')) {
            throw new \Exception('getTranslations method required');
        }

        if (!$locale){
            throw new \Exception('Locale is required');
        }


        $method = Inflector::camelize('get_' . $slugField);

        $strategy = new TextStrategy($this->container);
        $strategy->setType($type);
        $context = new SlugGenerator($strategy);
        $context->generate($locale, $object->getId(), $object->{$method}());


    }

    public function generate($object, $slugField = 'slug', $type = SlugEntity::TYPE_PAGE)
    {

        $defined = false;
        if(defined(get_class($object) . '::SLUG_TYPE')){
            $defined = true;
            $type = $object::SLUG_TYPE;
        } elseif ($type === null)
        {
            throw new \Exception('Type not defined');
        }


        if ($type != null && !$defined && !in_array($type, SlugEntity::$typeCollection)) {
            throw new \Exception('Slug type was not found');
        }

        if (!method_exists($object, 'getTranslations')) {
            throw new \Exception('getTranslations method required');
        }

        $locales = $this->container->getParameter('locales');
        $defaultLocale = $this->container->getParameter('locale');

        $textsForSlugs = [];
        $origName = null;

        $method = Inflector::camelize('get_' . $slugField);

        if (method_exists($object, $method) ) {
            $textsForSlugs[$defaultLocale] = $object->{$method}();
            $origName = $textsForSlugs[$defaultLocale];
        }

        if (is_object($object->getTranslations())) {
            foreach ($object->getTranslations()->getValues() as $row) {

                if ($row->getField() == $slugField) {
                    $textsForSlugs[$row->getLocale()] = $row->getContent();
                }
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

    public function get($itemId, $type, $locale = null)
    {

        $item = $this->repository->findOneBy(
            array(
                'item_id' => $itemId, 'type' => $type,
                'lang_id' => $locale ? $locale : $this->getLocale(), 'active' => 1
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

    public function getUrl($itemId, $type)
    {
        if(isset(self::$cache[$type][$itemId])) {
            return self::$cache[$type][$itemId];
        }
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

        if ($urlSlug) {
            $url = $this->router->generate($slug, $params);
        }
        self::$cache[$type][$itemId] = $url;

        return $url;
    }

    public function getPath($itemId, $type, $lang = false)
    {
        if(isset(self::$cachePath[$type][$itemId])) {
            return self::$cachePath[$type][$itemId];
        }

        $locale = $this->getLocale();

        $slug = 'food_slug';
        $urlSlug = $this->get($itemId, $type);
        $params = ['slug' => $urlSlug];

        $url = 'error';
        if ($locale != $this->defaultLocale && $lang) {
            $slug = 'food_slug_lang';
            $params['_locale'] = $locale;
            $url = $locale . "/" . $url;
        }

        if ($urlSlug) {
            $url = ltrim(str_replace(["/app_dev.php/"], "", $this->router->generate($slug, $params, UrlGeneratorInterface::ABSOLUTE_PATH)), '/');

        }
        self::$cachePath[$type][$itemId] = $url;
        return $url;
    }

    public function ajaxURL($route, $params = []) {

        $locale = $this->getLocale();
        $params['_locale'] = $locale;
        return $this->router->generate($route, $params);
    }


    public function generateURL($route, $params = [])
    {
        if (count($params) && array_key_exists('_locale', $params)){
            $params = $params[0];
            $recLoc = $this->request->getLocale();
            $this->request->setLocale($params['_locale']);
            $default = ['_locale' => $this->getLocale()];
            $url =  $this->router->generate($route, array_merge($params, $default));
            $this->request->setLocale($recLoc);

            return $url;
        }
        else {
            $default = ['_locale' => $this->getLocale()];
            return $this->router->generate($route, array_merge($params, $default));
        }

    }


    public function generatePath($route, $params = [])
    {
        $default = ['_locale' => $this->getLocale()];
        return $this->router->generate($route, array_merge($params, $default));
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
        if(!$contentId) {
            throw new \Exception('Parameter '.$name.' not found');
        }
        return $this->getUrl($contentId, $type);
    }

    public function checkNotFound()
    {

        $request = $this->request->getLocale();
        $availableLocales = $this->localeCollection;
        $disabledLocales = $this->container->getParameter('locales_hidden');

        if(count($disabledLocales)) {
            foreach ($availableLocales as $key => $locale) {
                if (in_array($locale, $disabledLocales)) {
                    unset($availableLocales[$key]);
                }
            }
        }

        if (!in_array($request, $availableLocales)) {
            throw new NotFoundHttpException();
        }

    }

}
