<?php

namespace Food\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Food\AppBundle\Entity\Slug;

class SlugController extends Controller
{
    public function processAction(Request $request, $slug)
    {
        die("MOJO");
        // if we have uppercase letters - permanently redirect to lowercase version
        if (preg_match('#[A-Z]#', $slug)) {
            $queryString = $request->getQueryString();
            $url = $this->generateUrl('fish_parado_slug', ['slug' => mb_strtolower($slug, 'utf-8')], true);

            return new RedirectResponse(sprintf('%s%s', $url, !empty($queryString) ? '?' . $queryString : ''), 301);
        }

        $slugUtil = $this->get('fish.parado.utils.slug');
        $slugRepo = $this->getDoctrine()->getRepository('FishCommonBundle:Slug');
        $repo = $this->getDoctrine()->getRepository('FishParadoBundle:Category');
        $slugRow = $slugUtil->getOneByName($slug);

        // check if slug is active. If not - redirect to next slug with 301
        if (!empty($slugRow) && !$slugRow->getIsActive()) {
            $slugRow = $slugRepo->findOneBy([
                'item_id' => $slugRow->getItemId(),
                'lang_id' => $slugRow->getLangId(),
                'type' => $slugRow->getType(),
                'is_active' => true,
            ]);

            if (empty($slugRow)) return $this->forward('FishParadoBundle:Error404:render');

            return $this->redirect($this->generateUrl('fish_parado_slug', ['slug' => $slugRow->getName()]), 301);
        }

        if ($slugRow == null) {
            if ($slug != null) return $this->forward('FishParadoBundle:Error404:render');
            else {
                $slug = $slugUtil->getFirstMainSlug();
                $slugRow = $slugUtil->getOneByName($slug);
            }
        }

        $slugUtil->set($slug);
        $slugUtil->setMain($slugUtil->getTopCategorySlug($slug));

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
    }
}