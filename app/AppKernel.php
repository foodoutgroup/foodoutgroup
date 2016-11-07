<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{

    /**
     * Constructor.
     *
     * @param string  $environment The environment
     * @param bool    $debug       Whether to enable debugging or not
     *
     * @api
     */
    public function __construct($environment, $debug)
    {
        date_default_timezone_set( 'Europe/Vilnius' );
        parent::__construct($environment, $debug);
    }

    public function registerBundles()
    {
        $bundles = array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Food\DishesBundle\FoodDishesBundle(),
            new Food\AppBundle\FoodAppBundle(),
            new Food\CartBundle\FoodCartBundle(),
            new Food\OrderBundle\FoodOrderBundle(),
            new Food\UserBundle\FoodUserBundle(),
            new \FOS\UserBundle\FOSUserBundle(),
            new Food\PlacesBundle\FoodPlacesBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Sonata\jQueryBundle\SonatajQueryBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\CoreBundle\SonataCoreBundle(),
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
            new A2lix\TranslationFormBundle\A2lixTranslationFormBundle(),
            new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
            new Sonata\UserBundle\SonataUserBundle('FoodUserBundle'),
            new Sonata\EasyExtendsBundle\SonataEasyExtendsBundle(),
            new Application\Sonata\UserBundle\ApplicationSonataUserBundle(),
            new Food\SmsBundle\FoodSmsBundle(),
            new Evp\Bundle\WebToPayBundle\EvpWebToPayBundle(),
            new Food\SupportBundle\FoodSupportBundle(),
            new Trsteel\CkeditorBundle\TrsteelCkeditorBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new Food\MonitoringBundle\FoodMonitoringBundle(),
            new Food\ApiBundle\FoodApiBundle(),
            new Pirminis\GatewayBundle\PirminisGatewayBundle(),
            new Food\ReportBundle\FoodReportBundle(),
            new Ob\HighchartsBundle\ObHighchartsBundle(),
            new Knp\Bundle\SnappyBundle\KnpSnappyBundle(),
            new Food\CallCenterBundle\CallCenterBundle(),
            new Lexik\Bundle\TranslationBundle\LexikTranslationBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }

    public function getCharset()
    {
        return 'UTF-8';
    }
}
