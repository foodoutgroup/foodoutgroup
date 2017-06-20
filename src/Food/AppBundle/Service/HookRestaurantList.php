<?php
namespace Food\AppBundle\Service;

use Food\AppBundle\Entity\Slug;
use Food\OrderBundle\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HookRestaurantList {

    private $container;
    private $params = [];

    /**
     * HookBlog constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function setParams($params)
    {
        $this->params = array_values($params);
    }

    public function build()
    {

        $params = $this->params;
        if(count($params)) {
            unset($params[0]);
        }

        $request = $this->container->get('request');

        $metaTitle = '';
        $metaDescription = '';

        $placeService = $this->container->get('food.places');
        $kitchenCollection = $placeService->getKitchenCollectionFromSlug($params, $request);

        if (count($kitchenCollection)) {
            list($first,) = $kitchenCollection;
            $metaTitle = $first->getMetaTitle();
            $metaDescription = $first->getMetaDescription();
        }

        return [
            'template' => 'FoodPlacesBundle:City:index.html.twig',
            'params' => [
                'city' => null,
                'recommended' => in_array('recom',$params), // todo MULTI-L param for recommended list
                'rush_hour' => in_array('rush', $params), // todo MULTI-L param for rush_hour list
                'location' => $this->container->get('food.location')->get(),
                'userAllAddress' => $placeService->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => implode("/", $params),
                'kitchen_collection' => $kitchenCollection,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'current_url' => $request->getUri(),
                'current_url_path' => 'hey',
            ]
        ];
    }

}
