<?php

namespace Food\AppBundle\Command;

use Food\AppBundle\Service\MailService;
use Food\DishesBundle\Entity\Place;
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
        try {
            $dishes = $this->getContainer()->get('doctrine')->getRepository('FoodDishesBundle:Dish')->findBy(['id'=>4]);
            foreach ($dishes as $dish){
               $translations = $dish->getTranslations();

//                $dish->setAdditionalInfo('*Dėl patiekaluose esančių alergenų ar netoleravimą sukeliančių maisto medžiagų produktų, kreipkitės tiesiogiai į restorano darbuotojus.');
                $em->persist($dish);
                $em->flush();
            break;
            }
        } catch (\Exception $e) {
            $output->writeln('<fg=red>Something went horribly wrong</fg=red>');
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));

            throw $e;
        }

        $this->getContainer()->get('doctrine')->getConnection()->close();
    }

}