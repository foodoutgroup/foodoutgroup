<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Food\AppBundle\Entity\Slug;

class SlugController extends Controller
{

    private $repository;
    private $util;
    private $request;
    private $slug;

    public function processAction(Request $request, $slug)
    {

        $this->request = $request;
        $this->repository = $this->getDoctrine()->getRepository('FoodAppBundle:Slug');
        $this->util = $this->get('food.dishes.utils.slug');
        $this->slug = $this->get('slug');

        if(substr($slug, -1) == '/') {
            $slug = substr($slug, 0, -1);
        }


        $params = explode("/", $slug);

        $slug = $params[0];
        unset($params[0]);
        $qb = $this->getDoctrine()
            ->getRepository('FoodAppBundle:Slug')
            ->createQueryBuilder('s');

        $query = $qb->where('s.lang_id = :locale')
            ->andWhere('s.name = :name')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('s.deactivated_at'),
                $qb->expr()->lt('s.deactivated_at', ':dateDeactivated')
            ));

            $query->setParameters([
                'name' => $slug,
                'locale' => $request->getLocale(),
                'dateDeactivated' => date('Y-m-d H:i:s', time() - Slug::SLUG_LIFETIME)
            ]);

            $query
                ->orderBy('s.id', 'DESC')
                ->setMaxResults(1);

            $result = $query->getQuery()->execute();
            $slugRow = null;
            if(count($result)) {
                list($slugRow, ) = $result;
            }

        if ($this->slug->isBanned()) {
            $banned_page = $this->get('food.static')->getPage($this->get('food.app.utils.misc')->getParam('banned_page', true));
            if($banned_page->getSlug() != $slug) {
                return $this->redirect($this->slug->urlFromParam('page_banned', Slug::TYPE_PAGE), 302);
            }
        }

        if (!is_null($slugRow) && !$slugRow->isActive()) {
            $slugRow = $this->repository->findOneBy([
                'lang_id' => $slugRow->getLangId(),
                'orig_name' => $slugRow->getOrigName(),
                'active' => true,
            ]);

            if (empty($slugRow)) {
                $this->pageNotFound404($slug);
            }
            return $this->redirect($this->generateUrl('food_slug', ['slug' => $slugRow->getName()]), 301);
        }
        $dataOptions = [];
        if($slugRow != null) {
            $dataOptions = ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName(), 'params' => $params];
        }


        switch(($slugRow == null && $slug != null) ? null : $slugRow->getType()) {

            case Slug::TYPE_CITY:
                return $this->forward('FoodPlacesBundle:City:index', $dataOptions);

            case Slug::TYPE_PAGE:
                return $this->forward('FoodAppBundle:StaticPage:index', $dataOptions);

            case Slug::TYPE_KITCHEN:
                return $this->forward('FoodDishesBundle:Kitchen:index', $dataOptions);

            case Slug::TYPE_PLACE:
                return $this->forward('FoodDishesBundle:Place:index', $dataOptions);

            case Slug::TYPE_FOOD_CATEGORY:
                $place = $this->get('food.places')->getPlaceByCategory($slugRow->getItemId());
                $slugUtele = $this->get('food.dishes.utils.slug');
                $placeSlug = $slugUtele->getSlugByItem($place->getId(), Slug::TYPE_PLACE);

                $url = $this->generateUrl('food_slug', ['slug' => $placeSlug], true);
                $queryString = $request->getQueryString().'#'.$slug;
                return new RedirectResponse(sprintf('%s%s', $url, !empty($queryString) ? '?' . $queryString : ''), 301);

                // Sena logika, kai kategorijos turejo sub puslapius
//                return $this->forward(
//                    'FoodDishesBundle:Place:index',
//                    ['id' => $place->getId(), 'slug' => $placeSlug, 'categoryId' => $slugRow->getItemId()]
//                );
                break;
            case Slug::TYPE_BLOG_CATEGORY:
                return $this->forward('FoodBlogBundle:Default:categoryIndex', ['id' => $slugRow->getItemId()]);
                break;
            case Slug::TYPE_BLOG_POST:
                return $this->forward('FoodBlogBundle:Default:postIndex', ['id' => $slugRow->getItemId()]);
                break;
            case Slug::TYPE_DISH:
                die("todo dish page");
                break;
            default:
                $this->pageNotFound404($slug);
                break;
        }
    }


    private function pageNotFound404($slug) {
        $errorMessage = sprintf('User requested non-existant slug: "%s" Locale: "%s" IP: "%s" UserAgent: "%s"',
            $slug, $this->request->getLocale(), $this->request->getClientIp(), $this->request->headers->get('User-Agent')
        );
        $this->get('logger')->error($errorMessage);
        throw new NotFoundHttpException('Sorry page "'.$slug.'" does not exist');
    }
}