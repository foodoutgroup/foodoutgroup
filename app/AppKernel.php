<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
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
