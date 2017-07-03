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
        'lt' => array(
            'a' => 'a',
            'as' => 'ai',
            'ė' => 'e',
            'is' => 'i',
            'us' => 'au',
            'ys' => 'y',
        ),
    );

    /**
     * @var array
     */
    private $countryCharReplacements = array(
            'ą' => 'a',
            'č' => 'c',
            'ę' => 'e',
            'ė' => 'e',
            'į' => 'i',
            'š' => 's',
            'ų' => 'u',
            'ū' => 'u',
            'ž' => 'z',
            'ґ' => 'g', 'ё' => 'e', 'є' => 'e', 'ї' => 'i', 'і' => 'i',
            'а' => 'a', 'б' => 'b', 'в' => 'v',
            'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'i',
            'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
            'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch',
            'ы' => 'y', 'э' => 'e', 'ю' => 'u', 'я' => 'ya', 'é' => 'e',
            'ь' => '', 'ъ' => '',
            'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i',
            'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'Õ' => 'O', 'Ä' => 'A', 'Ö' => 'O', 'Ü' => 'U',
            'õ' => 'o', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u'
    );

    /**
     * @var array
     */
    private $countryCapitalCharReplacements = array(
            'Ą' => 'A',
            'Č' => 'C',
            'Ę' => 'E',
            'Ė' => 'E',
            'Į' => 'I',
            'Š' => 'S',
            'Ų' => 'U',
            'Ū' => 'U',
            'Ž' => 'Z',
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ж' => 'Zh', 'З' => 'Z',
            'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L',
            'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P',
            'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U',
            'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
            'Ш' => 'Sh', 'Щ' => 'Sht', 'Ъ' => 'A', 'Ь' => 'Y',
            'Ю' => 'Yu', 'Я' => 'Ya',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z', 'Ā' => 'A',  'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i',
            'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
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
    public function removeChars($lang, $text, $toLower = true, $removeSpecialChars = true)
    {
//        if (!in_array($lang, array('lt', 'ru', 'en', 'lv', 'ee'))) {
//            throw new \Exception('Undefined language');
//        }

        if ($toLower) {
            $text = strtr(mb_strtolower($text, 'utf-8'), $this->countryCharReplacements);
        } else {
            $text = strtr($text, $this->countryCharReplacements);
            $text = strtr($text, $this->countryCapitalCharReplacements);
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
        return $this->getContainer()->getParameter('locales');
    }

    /**
     * TODO - multilingual
     * @param string $name
     * @param null|string $lang
     *
     * @return string
     */
    public function getName($name, $lang = null)
    {
        $names = explode(' ', $this->cleanName($name, $lang));
        $namesConv = array();
        foreach ($names as $v) {
            $namesConv[] = $this->getTransformedName($v, $lang);
        }

        return count($namesConv) ? implode(' ', $namesConv) : $name;
    }

    /**
     * TODO - multiligual
     * @param string $name
     * @param null|string $lang
     *
     * @return string
     */
    protected function cleanName($name, $lang = null)
    {
        switch ($lang) {
            case 'lt':
                $countryPreg = '[^a-ž]';
                break;

            default:
                $countryPreg = '';
                break;
        }

        if (!empty($countryPreg)) {
            $name = mb_eregi_replace($countryPreg, ' ', $name);
        }
        $name = mb_eregi_replace('\s+', ' ', $name);
        $name = trim($name);
        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");

        return $name;
    }

    /**
     * TODO - multilingual
     * @param string $name
     * @param null|string $lang
     *
     * @return string
     */
    protected function getTransformedName($name, $lang = null)
    {
        $return = $name;

        if (isset($this->nameInflection[$lang])) {
            foreach ($this->nameInflection[$lang] as $from => $to) {
                if (mb_substr($return, -mb_strlen($from)) == $from) {
                    $return = mb_substr($return, 0, -mb_strlen($from));
                    $return .= $to;
                    break;
                }
            }
        }

        return $return;
    }

    public function getAllCharTranslations($text)
    {
        $allLang = $this->countryCharReplacements;
//        $tmpLangArr = array();
//
//        foreach ($allLang as $lang) {
//            $tmpLangArr = array_merge($tmpLangArr, $lang);
//        }

        $text = strtr(mb_strtolower($text, 'utf-8'), $allLang);
        $text = strtr($text, $allLang);
        $text = strtr($text, $allLang);
        $text = strtr($text, $allLang);

        return $text;

    }
}
