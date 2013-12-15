<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Food\AppBundle\Entity\Slug;


class SlugController extends Controller
{
    public function processAction(Request $request, $slug)
    {

        // if we have uppercase letters - permanently redirect to lowercase version
        if (preg_match('#[A-Z]#', $slug)) {
            // @todo - Reik sutvarkyt
            $queryString = $request->getQueryString();
            $url = $this->generateUrl('food_slug', ['slug' => mb_strtolower($slug, 'utf-8')], true);
            return new RedirectResponse(sprintf('%s%s', $url, !empty($queryString) ? '?' . $queryString : ''), 301);
        }

        $slugUtil = $this->get('food.dishes.utils.slug');
        $slugRepo = $this->getDoctrine()->getRepository('FoodAppBundle:Slug');
        if(substr($slug, -1) == '/') {
            $slug = substr($slug, 0, -1);
        }

        $slugRow = $slugUtil->getOneByName($slug);

        // check if slug is active. If not - redirect to next slug with 301
        if (!empty($slugRow) && !$slugRow->getIsActive()) {
            $slugRow = $slugRepo->findOneBy([
                'item_id' => $slugRow->getItemId(),
                'lang_id' => $slugRow->getLangId(),
                'type' => $slugRow->getType(),
                'is_active' => true,
            ]);
            if (empty($slugRow)) {
                throw new NotFoundHttpException('Sorry page does not exist!');
            }
            return $this->redirect($this->generateUrl('food_slug', ['slug' => $slugRow->getName()]), 301);
        }

        if ($slugRow == null) {
            $logger->info("-+ Slugo irasas DB nerastas");
            if ($slug != null) {
                throw new NotFoundHttpException('Sorry page does not exist');
            }
            else {
                // $slug = $slugUtil->getFirstMainSlug(); // @todo clean
                // $slugRow = $slugUtil->getOneByName($slug);
            }
        }

        switch($slugRow->getType()) {
            case Slug::TYPE_TEXT:
                return $this->forward('FoodAppBundle:Static:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);
                break;

            case Slug::TYPE_KITCHEN:
                return $this->forward('FoodDishesBundle:Kitchen:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);
                break;

            case Slug::TYPE_PLACE:
                return $this->forward('FoodDishesBundle:Place:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);
                break;

            case Slug::TYPE_FOOD_CATEGORY:
                return $this->forward('FoodDishesBundle:FoodCategory:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);
                break;

            default:
                break;
        }

        /*
        $slugUtil->set($slug);
        $slugUtil->setMain($slugUtil->getTopCategorySlug($slug)); // @todo clean

        // @todo - sukurti clean puslapius kurie suvirshkintu ir apdorotu viska
        if ($slugRow->getType() == Slug::TYPE_CATEGORY) {
            // since categories can have attached custom action and/or template, we select this category first
            $category = $repo->findOneById($slugRow->getItemId());

            $action = $category->getAction() ?: 'list';
            $template = $category->getTemplate() ?: 'list.html.twig';

            return $this->forward(
                "FishParadoBundle:Category:{$action}",
                ['template' => $template, 'category' => $category]
            );
        }

        if ($slugRow->getType() == Slug::TYPE_BRAND) {
            return $this->forward(
                'FishParadoBundle:Brand:index', ['request' => $request, 'id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]
            );
        }

        if ($slugRow->getType() == Slug::TYPE_PRODUCT) return $this->forward('FishParadoBundle:Product:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);

        if ($slugRow->getType() == Slug::TYPE_TEXT) return $this->forward('FishParadoBundle:Text:item', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);
        */
    }
}