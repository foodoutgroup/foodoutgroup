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

        return [
            'template' => 'FoodPlacesBundle:City:index.html.twig',
            'params' => [
                'city' => null,
                'rush_hour' => in_array('asfasfas', $params), // todo MULTI-L param for rush_hour list
                'location' => $this->container->get('food.location')->get(),
                'userAllAddress' => $this->container->get('food.places')->getCurrentUserAddresses(),
                'delivery_type_filter' => $this->container->get('session')->get('delivery_type', OrderService::$deliveryDeliver),
                'slug_filter' => implode("/", $params),
                'kitchen_collection' => [],
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'current_url' => $request->getUri(),
                'current_url_path' => $this->container->get('slug')->getUrl($this->container->get('food.app.utils.misc')->getParam('page_restaurant_list', 0), Slug::TYPE_PAGE),
            ]
        ];
    }

}
