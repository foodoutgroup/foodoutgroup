<?php
namespace Food\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class HookBestOffer {

    private $templating;
    private $em;

    public function __construct(EngineInterface $templating, EntityManager $entityManager)
    {
        $this->templating = $templating;
        $this->em = $entityManager;
    }


    public function build()
    {
        $params = [
            'hideAllOffersLink' => true,
            'best_offers' => $this->em->getRepository('FoodPlacesBundle:BestOffer')->getActiveOffers()
        ];

        return $this->templating->render('@FoodApp/Hook/best_offers.html.twig', $params);
    }
}
