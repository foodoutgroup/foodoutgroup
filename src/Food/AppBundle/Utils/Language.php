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

    private $nameInflection = array(
        'a' => 'a',
        'as' => 'ai',
        'ė' => 'e',
        'is' => 'i',
        'us' => 'au',
        'ys' => 'y'
    ) ;

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
     * Get all languages. Has language IDs as keys.
     * @return array
     */
    public function getAll()
    {
        return $this->getContainer()->getParameter('available_locales');
    }


    /**
     * TODO - multilingual
     */
    public function getName ($name, $lang = null)
    {
        // TODO - multilingual
        if ($lang != 'lt') {
            return $name;
        }

        $names = explode( ' ', $this->cleanName($name, $lang) ) ;
        $namesConv = array() ;
        foreach ( $names as $v ) {
            $namesConv[] = $this->getTransformedName($v, $lang) ;
        }

        return count($namesConv) ? implode(' ', $namesConv) : $name ;
    }

    /**
     * TODO - multiligual
     */
    protected function cleanName ($name, $lang = null) {

        $name = mb_eregi_replace('[^a-ž]', ' ', $name) ;
        $name = mb_eregi_replace('\s+', ' ', $name) ;
        $name = trim($name) ;
        $name = mb_convert_case($name, MB_CASE_TITLE) ;

        return $name ;
    }

    /**
     * TODO - multilingual
     */
    protected function getTransformedName ($name, $lang = null) {

        $return = $name ;

        foreach ( $this->nameInflection as $from=>$to ) {
            if ( mb_substr( $return, -mb_strlen($from) ) == $from ) {
                $return = mb_substr( $return, 0, -mb_strlen($from) ) ;
                $return .= $to ;
                break ;
            }
        }

        return $return ;
    }
}