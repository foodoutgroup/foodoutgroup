<?php

namespace Food\PlacesBundle\Command;

use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePlacePointWorktimeCommand extends ContainerAwareCommand
{

    private $em;
    private $placePointService;

    protected function configure()
    {
        $this
            ->setName('placepoint:worktime:update')
            ->setDescription('Update worktimes value for placepoint')
            ->addArgument(
                'placepoint_id',
                InputArgument::OPTIONAL,
                'Placepoint ID'
            )
            ->addOption(
                'for-all',
                null,
                InputOption::VALUE_NONE,
                'Set hash for placepoints who has no hash'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->placePointService = $this->getContainer()->get('food.place_point_service');

        if ($input->getOption('for-all')) {
            $placePoints = $this->em->getRepository('FoodDishesBundle:PlacePoint')
                ->createQueryBuilder('pp')
                ->where('pp.active = 1');

            $placePoints = $placePoints
                ->getQuery()
                ->getResult();

        } else if ($placePointId = $input->getArgument('placepoint_id')) {
            $placePoints = $this->em->getRepository('FoodDishesBundle:PlacePoint')
                ->createQueryBuilder('pp')
                ->where('pp.active = 1')
                ->andWhere('pp.id = :id')
                ->setParameter('id', $placePointId);

            $placePoints = $placePoints
                ->getQuery()
                ->getResult();
        } else {
            throw new \RuntimeException('Not enough arguments.');
        }

        foreach ($placePoints as $placePoint) {
            $this->placePointService->updatePlacePointWorktime($placePoint);
        }
    }
}