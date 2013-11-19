<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
// use Fish\CommonBundle\Entity\Language as LanguageEntity;
use Axelarge\ArrayTools\Arr;

class Language
{
    use Traits\Service;

    /**
     * Get current language.
     * @return LanguageEntity
     */
    public function getCurrent()
    {
        $route = $this->service('fish.common.utils.route');
        $repo = $this->repo('FishCommonBundle:Language');

        return $repo->findOneBy(['name' => $route->getLocale(), 'is_active' => true]);
    }

    /**
     * Get default language.
     * @return Language
     */
    public function getDefault()
    {
        $repo = $this->repo('FishCommonBundle:Language');
        $defaultLocale = Arr::getOrElse($this->container()->parameters, 'locale', '');

        return $repo->findOneBy(['name' => $defaultLocale, 'is_active' => true]);
    }

    /**
     * Get all languages. Has language IDs as keys.
     * @return array
     */
    public function getAll()
    {
        $repo = $this->repo('FishCommonBundle:Language');
        $configLocales = Arr::getOrElse($this->container()->parameters, 'locales', []);
        return $repo->findBy(['name' => $configLocales, 'is_active' => true]);
    }

    public function getById($id)
    {
        $repo = $this->repo('FishCommonBundle:Language');

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