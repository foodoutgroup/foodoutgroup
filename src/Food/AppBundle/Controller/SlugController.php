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
        if ((!isset($_SESSION['is_allowed']) || $_SESSION['is_allowed']!== true) && $_SERVER['REMOTE_ADDR'] != '88.119.87.158' && $_SERVER['REMOTE_ADDR']!="80.232.220.207" && $_SERVER['REMOTE_ADDR']!="78.56.52.159") {
            ?>
            <html>
            <head>
                <title>Foodout.lv - coming soon</title>
            </head>
            <body>
            <div style="width: 100%; padding-top: 50px; text-align: center;">
                <img src="/bundles/foodapp/images/foodout_no_domain.jpg" alt="Foodout.lv" />
            </div>
            </body>
            </html>
            <?php
            die();
        }
        // Check if user is not banned
        $ip = $request->getClientIp();
        // Dude is banned - hit him
        if ($this->get('food.app.utils.misc')->isIpBanned($ip)) {
            return $this->redirect($this->generateUrl('banned'), 302);
        }

        // if we have uppercase letters - permanently redirect to lowercase version
        if (preg_match('#[A-Z]#', $slug)) {
            $queryString = $request->getQueryString();
            $url = $this->generateUrl('food_slug', ['slug' => mb_strtolower($slug, 'utf-8')], true);
            return new RedirectResponse(sprintf('%s%s', $url, !empty($queryString) ? '?' . $queryString : ''), 301);
        }

        $slugUtil = $this->get('food.dishes.utils.slug');
        $slugRepo = $this->getDoctrine()->getRepository('FoodAppBundle:Slug');
        if(substr($slug, -1) == '/') {
            $slug = substr($slug, 0, -1);
        }

        $slugRow = $slugUtil->getOneByName($slug, $request->getLocale());

        // check if slug is active. If not - redirect to next slug with 301
        if (!empty($slugRow) && !$slugRow->isActive()) {
            $slugRow = $slugRepo->findOneBy([
                'item_id' => $slugRow->getItemId(),
                'lang_id' => $slugRow->getLangId(),
                'type' => $slugRow->getType(),
                'is_active' => true,
            ]);
            if (empty($slugRow)) {
                // Log da shit about slug problems :)
                $errorMessage = sprintf(
                    'User requested non-existant slug: "%s" Locale: "%s" IP: "%s" UserAgent: "%s"',
                    $slug,
                    $request->getLocale(),
                    $request->getClientIp(),
                    $request->headers->get('User-Agent')
                );
                $this->get('logger')->error($errorMessage);

                throw new NotFoundHttpException('Sorry page "'.$slug.'" does not exist!');
            }
            return $this->redirect($this->generateUrl('food_slug', ['slug' => $slugRow->getName()]), 301);
        }

        if ($slugRow == null) {
            if ($slug != null) {
                // Log da shit about slug problems :)
                $errorMessage = sprintf(
                    'User requested non-existant slug: "%s" Locale: "%s" IP: "%s" UserAgent: "%s"',
                    $slug,
                    $request->getLocale(),
                    $request->getClientIp(),
                    $request->headers->get('User-Agent')
                );
                $this->get('logger')->error($errorMessage);

                throw new NotFoundHttpException('Sorry page "'.$slug.'" does not exist');
            }
        }

        switch($slugRow->getType()) {
            case Slug::TYPE_TEXT:
                return $this->forward('FoodAppBundle:Static:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);

            case Slug::TYPE_KITCHEN:
                return $this->forward('FoodDishesBundle:Kitchen:index', ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName()]);

            case Slug::TYPE_PLACE:
                return $this->forward(
                    'FoodDishesBundle:Place:index',
                    ['id' => $slugRow->getItemId(), 'slug' => $slugRow->getName(), 'categoryId' => '']
                );

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

            default:
                break;
        }
    }
}