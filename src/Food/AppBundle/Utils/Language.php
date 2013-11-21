<?php

namespace Food\AppBundle\Utils;

use Doctrine\Common\Collections\ArrayCollection;
use Food\AppBundle\Traits;
// use Fish\CommonBundle\Entity\Language as LanguageEntity;
use Axelarge\ArrayTools\Arr;
use Symfony\Component\DependencyInjection\Container;

class Language
{
    use Traits\Service;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\Container
     */
    public function getContainer()
    {
        return $this->container;
    }



    /**
     * Get current language.
     * @return LanguageEntity
     *
     * @todo FIX
     */
    public function getCurrent()
    {
        $route = $this->service('food.app.utils.route');
        $repo = $this->repo('FoodAppBundle:Language');

        return $repo->findOneBy(['name' => $route->getLocale(), 'is_active' => true]);
    }

    /**
     * Get default language.
     * @return Language
     *
     * @todo FIX
     */
    public function getDefault()
    {
        $repo = $this->repo('FoodAppBundle:Language');
        $defaultLocale = Arr::getOrElse($this->container()->parameters, 'locale', '');

        return $repo->findOneBy(['name' => $defaultLocale, 'is_active' => true]);
    }

    /**
     * Get all languages. Has language IDs as keys.
     * @return array
     */
    public function getAll()
    {
        return $this->getContainer()->getParameter('available_locales');
    }

    public function getById($id)
    {
        $repo = $this->repo('FoodAppBundle:Language');

        return $repo->findOneBy(['id' => $id, 'is_active' => true]);
    }

    /**
     * Switch language
     * @param  int|string $language Might be ID or identifier.
     * @return void
     */
    public function switchLanguage(LanguageEntity $language)
    {
        $request = $this->service('request');
        $request->setLocale($language->getName());
    }
}