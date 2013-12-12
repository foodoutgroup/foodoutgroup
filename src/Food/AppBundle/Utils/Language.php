<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
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
     * @param $lang
     * @param $text
     * @throws \Exception
     * @return string
     */
    public function removeChars($lang, $text)
    {
        switch($lang) {
            case 'lt':
                return $this->_removeLtChars($text);
                break;
            case 'ru':
                return $this->_removeRuChars($text);
                break;
            case 'en':
                return $this->_removeEnChars($text);
                break;
        }
        throw new \Exception('Undefined language');
    }

    /**
     * @param $text
     * @return string
     */
    private function _removeLtChars($text)
    {
        $chars = array(
            'ą' => 'a',
            'č' => 'c',
            'ę' => 'e',
            'ė' => 'e',
            'į' => 'i',
            'š' => 's',
            'ų' => 'u',
            'ū' => 'u',
            'ž' => 'z',
            '#' => '-',
            '&' => 'and'
        );
        return strtr(mb_strtolower($text, 'utf-8'), $chars);
    }


    /**
     * @param $text
     * @return string
     */
    private function _removeRuChars($text)
    {
        $chars = array(
            'ґ'=>'g','ё'=>'e','є'=>'e','ї'=>'i','і'=>'i',
            'а'=>'a', 'б'=>'b', 'в'=>'v',
            'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'e',
            'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'i',
            'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n',
            'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s',
            'т'=>'t', 'у'=>'u', 'ф'=>'f', 'х'=>'h',
            'ц'=>'c', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch',
            'ы'=>'y', 'э'=>'e', 'ю'=>'u', 'я'=>'ya', 'é'=>'e', '&'=>'and',
            'ь'=>'', 'ъ' => '', '#' => '-'
        );
        return strtr(mb_strtolower($text, 'utf-8'), $chars);
    }

    private function _removeEnChars($text)
    {
        $chars = array(
            '#' => '-',
            '&' => 'and'
        );
        return strtr(mb_strtolower($text, 'utf-8'), $chars);
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


    /**
     * Switch language
     * @param  \LanguageEntity $language Might be ID or identifier.
     * @return void
     */
    public function switchLanguage(\LanguageEntity $language)
    {
        $request = $this->service('request');
        $request->setLocale($language->getName());
    }
}