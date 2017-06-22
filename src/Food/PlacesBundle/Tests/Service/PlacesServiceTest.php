<?php
namespace Food\PlacesBundle\Tests\Service;

use Food\DishesBundle\Entity\Place;
use Food\DishesBundle\Entity\PlacePoint;
use Food\PlacesBundle\Service\PlacesService;
use Food\AppBundle\Test\WebTestCase;

class PlacesServiceTest extends WebTestCase {

    public function testSettersGetters()
    {
        $container = $this->getMockBuilder('\Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();
        $session = $this->getMockBuilder('\Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();

        $placesService = new PlacesService();

        $placesService->setContainer($container);
        $gotContainer = $placesService->getContainer();
        $this->assertEquals($container, $gotContainer);

        $container->expects($this->once())
            ->method('get')
            ->with('session')
            ->will($this->returnValue($session));

        $gotSession = $placesService->getSession();
        $this->assertEquals($session, $gotSession);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetPlaceException()
    {
        $placesService = new PlacesService();

        $placesService->getPlace(null);
    }

    public function testGetPlace()
    {
        $placeId = 15;

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get', 'getParameter')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $placeRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $place = $this->getMockBuilder('Food\DishesBundle\Entity\Place')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('FoodDishesBundle:Place')
            ->will($this->returnValue($placeRepository));

        $placeRepository->expects($this->once())
            ->method('find')
            ->with($placeId)
            ->will($this->returnValue($place));

        $placesService = new PlacesService();
        $placesService->setContainer($container);

        $gotPlace = $placesService->getPlace($placeId);

        $this->assertEquals($place, $gotPlace);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSavePlaceException()
    {
        $placesService = new PlacesService();

        $placesService->savePlace(array());
    }

    public function testSavePlace()
    {
        $place = new Place();

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($place);

        $entityManager->expects($this->once())
            ->method('flush');

        $placesService = new PlacesService();
        $placesService->setContainer($container);

        $placesService->savePlace($place);
    }

    public function testGetAvailableCities()
    {
        $expectedCities = array('Vilnius', 'Kaunas', 'Klaipėda');
        $dbCities = array(
            array('city' => 'Vilnius'),
            array('city' => 'Kaunas'),
            array('city' => 'Klaipėda'),
        );
        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );
        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $connection->expects($this->once())
            ->method('fetchAll')
            ->with("SELECT DISTINCT(pp.city) FROM `place_point` pp, `place` p WHERE pp.place = p.id AND pp.active=1 AND p.active = 1")
            ->will($this->returnValue($dbCities));

        $placesService = new PlacesService();
        $placesService->setContainer($container);


    }

    public function testGetActiveCategories()
    {
        $expectedCategories = array('categorie1', 'categorie2');
        $placeId = 167;

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $place = $this->getMockBuilder('Food\DishesBundle\Entity\Place')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('FoodDishesBundle:FoodCategory')
            ->will($this->returnValue($categoryRepository));

        $place->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($placeId));

        $categoryRepository->expects($this->once())
            ->method('findBy')
            ->with(array(
                    'place' => $placeId,
                    'active' => 1,
                ),
                array(
                    'lineup' => 'DESC'
                ))
            ->will($this->returnValue($expectedCategories));

        $placesService = new PlacesService();
        $placesService->setContainer($container);

        $gotCategories = $placesService->getActiveCategories($place);
        $this->assertEquals($expectedCategories, $gotCategories);
    }

    public function testGetPlaceByCategory()
    {
        $expectedPlace = new Place();
        $categoryId = 167;

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $category = $this->getMockBuilder('Food\DishesBundle\Entity\FoodCategory')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('FoodDishesBundle:FoodCategory')
            ->will($this->returnValue($categoryRepository));

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->will($this->returnValue($category));

        $category->expects($this->once())
            ->method('getPlace')
            ->will($this->returnValue($expectedPlace));

        $placesService = new PlacesService();
        $placesService->setContainer($container);

        $gotPlace = $placesService->getPlaceByCategory($categoryId);
        $this->assertEquals($expectedPlace, $gotPlace);
    }

    public function testGetPlaceByCategoryNoneFound()
    {
        $expectedPlace = false;
        $categoryId = 167;

        $container = $this->getMock(
            'Symfony\Component\DependencyInjection\Container',
            array('get')
        );

        $doctrine = $this->getMockBuilder('\Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $categoryRepository = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->with('doctrine')
            ->will($this->returnValue($doctrine));

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($entityManager));

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with('FoodDishesBundle:FoodCategory')
            ->will($this->returnValue($categoryRepository));

        $categoryRepository->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->will($this->returnValue(false));

        $placesService = new PlacesService();
        $placesService->setContainer($container);

        $gotPlace = $placesService->getPlaceByCategory($categoryId);
        $this->assertEquals($expectedPlace, $gotPlace);
    }

    public function testGetFullRangeWorkTimes()
    {
        $placeService = new PlacesService();
        $place = new Place();
        $placePoint = new PlacePoint();

        $placePoint->setWd1Start('09:00')
            ->setWd1End('20:00')
            ->setWd5Start('10:00')
            ->setWd5End('01:00')
            ->setWd7Start('Nedir')
            ->setWd7End('Nedir');

        $day = date("w");

        $mondayShift = $day - 1;
        if ($mondayShift < 0) {
            $mondayShift = "+".(-$mondayShift);
        } else {
            $mondayShift = "-".$mondayShift;
        }
        $fridayShift = $day - 5;
        if ($fridayShift < 0) {
            $fridayShift = "+".(-$fridayShift);
        } else {
            $fridayShift = "-".$fridayShift;
        }
        $sundayShift = $day - 7;
        if ($sundayShift < 0) {
            $sundayShift = "+".(-$sundayShift);
        } else {
            $sundayShift = "-".$sundayShift;
        }

        $mondayGraph = $placeService->getFullRangeWorkTimes($place, $placePoint, $mondayShift." day");
        $fridayGraph = $placeService->getFullRangeWorkTimes($place, $placePoint, $fridayShift." day");
        $sundayGraph = $placeService->getFullRangeWorkTimes($place, $placePoint, $sundayShift." day");

        $expectedMondayGraph = array(
            '10:00',
            '10:30',
            '11:00',
            '11:30',
            '12:00',
            '12:30',
            '13:00',
            '13:30',
            '14:00',
            '14:30',
            '15:00',
            '15:30',
            '16:00',
            '16:30',
            '17:00',
            '17:30',
            '18:00',
            '18:30',
            '19:00',
            '19:30',
            '20:00',
        );

        $expectedFridayGraph = array(
            '11:00',
            '11:30',
            '12:00',
            '12:30',
            '13:00',
            '13:30',
            '14:00',
            '14:30',
            '15:00',
            '15:30',
            '16:00',
            '16:30',
            '17:00',
            '17:30',
            '18:00',
            '18:30',
            '19:00',
            '19:30',
            '20:00',
            '20:30',
            '21:00',
            '21:30',
            '22:00',
            '22:30',
            '23:00',
            '23:30',
            '24:00',
        );

        $expectedSundayGraph = array();

        $this->assertEquals($expectedMondayGraph, $mondayGraph);
        $this->assertEquals($expectedFridayGraph, $fridayGraph);
        $this->assertEquals($expectedSundayGraph, $sundayGraph);
    }

    public function testGetFullRangeWorkTimesNoPoint()
    {
        $placeService = new PlacesService();
        $place = new Place();
        $placePoint = new PlacePoint();

        $placePoint->setWd1Start('08:00')
            ->setWd1End('20:00');

        $place->addPoint($placePoint);

        $day = date("w");

        $mondayShift = $day - 1;
        if ($mondayShift < 0) {
            $mondayShift = "+".(-$mondayShift);
        } else {
            $mondayShift = "-".$mondayShift;
        }


        $mondayGraph = $placeService->getFullRangeWorkTimes($place, null, $mondayShift." day");

        $expectedMondayGraph = array(
            '09:00',
            '09:30',
            '10:00',
            '10:30',
            '11:00',
            '11:30',
            '12:00',
            '12:30',
            '13:00',
            '13:30',
            '14:00',
            '14:30',
            '15:00',
            '15:30',
            '16:00',
            '16:30',
            '17:00',
            '17:30',
            '18:00',
            '18:30',
            '19:00',
            '19:30',
            '20:00',
        );

        $this->assertEquals($expectedMondayGraph, $mondayGraph);
    }
}