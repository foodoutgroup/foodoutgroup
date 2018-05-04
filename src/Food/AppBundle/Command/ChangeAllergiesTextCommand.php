<?php

namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\DishLocalized;
use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlaceLocalized;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeAllergiesTextCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('import:allergies')
            ->setDescription('change zavalas delivery zones');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $locales = $this->getContainer()->getParameter('locales');
        $locale = $this->getContainer()->getParameter('locale');

        foreach ($locales as $key => $localeItem) {
            if ($localeItem == $locale) {
                unset($locales[$key]);
            }
        }

        $text = '*Dėl patiekaluose esančių alergenų ar netoleravimą sukeliančių maisto medžiagų produktų, kreipkitės tiesiogiai į restorano darbuotojus.';

        try {
            $dishes = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:Dish')->findAll();

            foreach ($dishes as $dish) {
                $dish->setAdditionalInfo($text);

                foreach ($locales as $loc) {
                    $trans = new DishLocalized();
                    $trans->setObject($dish);
                    $trans->setField('additionalInfo');
                    $trans->setLocale($loc);
                    $trans->setContent($text);
                    $em->persist($trans);
                }
                $em->persist($dish);
                $em->flush();
            }
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}