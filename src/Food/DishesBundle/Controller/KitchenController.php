<?php

namespace Food\DishesBundle\Controller;

use Sonata\DoctrineORMAdminBundle\Tests\Filter\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class KitchenController extends Controller
{

    public function indexAction($id, $slug)
    {
        $kitchen = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen')->find($id);
        return $this->render('FoodDishesBundle:Kitchen:index.html.twig', array('kitchen' => $kitchen));
    }

    /**
     * Rodomas restoranu sarase
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function kitchenlistAction()
    {
        $list = $this->getKitchens();
        return $this->render('FoodDishesBundle:Kitchen:list_items.html.twig', array('list' => $list));
    }


    /**
     * @todo - patikrinti reikalinguma. Nebeliko ikonkiu prie virtuviu
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function kitchenListWithImagesAction()
    {
        $list = $this->getKitchens();
        return $this->render('FoodDishesBundle:Kitchen:list_items_with_images.html.twig', array('list' => $list));
    }

    /**
     * @return array|\Food\DishesBundle\Entity\Kitchen[]
     */
    private function getKitchens()
    {
        $repository = $this->getDoctrine()->getRepository('FoodDishesBundle:Kitchen');

        /**
         * @var \Doctrine\ORM\QueryBuilder $qb
         */
        $qb = $repository->createQueryBuilder('k');

        $query = $qb
            ->leftJoin('k.places', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'p.active = 1')
            ->addSelect('k.id, k.name, COUNT(p.id) AS placeCount')
            ->where('k.visible = 1')
            ->groupBy('k.id')
            ->orderBy('placeCount', 'DESC')
            ->having('placeCount > 0')
            ->getQuery();

        return $query->getResult();
    }
}
