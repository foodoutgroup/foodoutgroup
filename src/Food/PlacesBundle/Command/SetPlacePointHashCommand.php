<?php

namespace Food\PlacesBundle\Command;

use Food\DishesBundle\Entity\PlacePoint;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SetPlacePointHashCommand extends ContainerAwareCommand
{

    private $em;
    private $placePointService;

    protected function configure()
    {
        $this
            ->setName('placepoint:hash:set')
            ->setDescription('Set hash value for placepoint')
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
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Force to regenerate hash'
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

            if (!$input->getOption('force')) {
                $placePoints->andWhere('pp.hash IS NULL');
            }

            $placePoints = $placePoints
                ->getQuery()
                ->getArrayResult();

        } else if ($placePointId = $input->getArgument('placepoint_id')) {
            $placePoints = $this->em->getRepository('FoodDishesBundle:PlacePoint')
                ->createQueryBuilder('pp')
                ->where('pp.active = 1')
                ->andWhere('pp.id = :id')
                ->setParameter('id', $placePointId);

            if (!$input->getOption('force')) {
                $placePoints->andWhere('pp.hash IS NULL');
            }

            $placePoints = $placePoints
                ->getQuery()
                ->getArrayResult();
        } else {
            throw new \RuntimeException('Not enough arguments.');
        }

        foreach ($placePoints as $placePoint) {
            $hash = $this->placePointService->generatePlacePointHash($placePoint);
            $placePoint->setHash($hash);
            $this->em->persist($placePoint);
        }
        $this->em->flush();
    }
}