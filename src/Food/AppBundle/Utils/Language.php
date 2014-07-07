<?php

namespace Food\AppBundle\Utils;

use Food\AppBundle\Traits;
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

    /**
     * @var array
     */
    private $countryCharReplacements = array(
        'lt' => array(
            'ą' => 'a',
            'č' => 'c',
            'ę' => 'e',
            'ė' => 'e',
            'į' => 'i',
            'š' => 's',
            'ų' => 'u',
            'ū' => 'u',
            'ž' => 'z',
        ),
        'en' => array(),
        'ru' => array(
            'ґ'=>'g','ё'=>'e','є'=>'e','ї'=>'i','і'=>'i',
            'а'=>'a', 'б'=>'b', 'в'=>'v',
            'г'=>'g', 'д'=>'d', 'е'=>'e', 'ё'=>'e',
            'ж'=>'zh', 'з'=>'z', 'и'=>'i', 'й'=>'i',
            'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n',
            'о'=>'o', 'п'=>'p', 'р'=>'r', 'с'=>'s',
            'т'=>'t', 'у'=>'u', 'ф'=>'f', 'х'=>'h',
            'ц'=>'c', 'ч'=>'ch', 'ш'=>'sh', 'щ'=>'sch',
            'ы'=>'y', 'э'=>'e', 'ю'=>'u', 'я'=>'ya', 'é'=>'e',
            'ь'=>'', 'ъ' => '',
        ),
    );

    /**
     * @var array
     */
    private $countryCapitalCharReplacements = array(
        'lt' => array(
            'Ą' => 'A',
            'Č' => 'C',
            'Ę' => 'E',
            'Ė' => 'E',
            'Į' => 'I',
            'Š' => 'S',
            'Ų' => 'U',
            'Ū' => 'U',
            'Ž' => 'Z',
        ),
        'en' => array(),
        'ru' => array(
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D',
            'Е'=>'E', 'Ж'=>'Zh', 'З'=>'Z',
            'И'=>'I', 'Й'=>'Y', 'К'=>'K', 'Л'=>'L',
            'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P',
            'Р'=>'R', 'С'=>'S', 'Т'=>'T', 'У'=>'U',
            'Ф'=>'F', 'Х'=>'H', 'Ц'=>'Ts', 'Ч'=>'Ch',
            'Ш'=>'Sh', 'Щ'=>'Sht', 'Ъ'=>'A', 'Ь'=>'Y',
            'Ю'=>'Yu', 'Я'=>'Ya',
        ),
    );

    /**
     * @var array
     */
    private $specialCharReplacements = array(
        '#' => '-',
        '&' => 'and'
    );

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
     * @param string $lang
     * @param string $text
     * @param boolean $removeSpecialChars
     * @param boolean $toLower
     * @throws \Exception
     * @return string
     */
    public function removeChars($lang, $text, $toLower = true, $removeSpecialChars=true)
    {
        if (!in_array($lang, array('lt', 'ru', 'en'))) {
            throw new \Exception('Undefined language');
        }

        if ($toLower) {
            $text = strtr(mb_strtolower($text, 'utf-8'), $this->countryCharReplacements[$lang]);
        } else {
            $text = strtr($text, $this->countryCharReplacements[$lang]);
            $text = strtr($text, $this->countryCapitalCharReplacements[$lang]);
        }

        if ($removeSpecialChars) {
            $text = strtr($text, $this->specialCharReplacements);
        }
        return $text;
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
     * @param string $name
     * @param null|string $lang
     *
     * @return string
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
     * @param string $name
     * @param null|string $lang
     *
     * @return string
     */
    protected function cleanName ($name, $lang = null)
    {
        $name = mb_eregi_replace('[^a-ž]', ' ', $name) ;
        $name = mb_eregi_replace('\s+', ' ', $name) ;
        $name = trim($name) ;
        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8") ;

        return $name ;
    }

    /**
     * TODO - multilingual
     * @param string $name
     * @param null|string $lang
     *
     * @return string
     */
    protected function getTransformedName ($name, $lang = null)
    {
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