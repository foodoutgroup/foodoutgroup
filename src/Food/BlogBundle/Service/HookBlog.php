<?php
namespace Food\BlogBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HookBlog {

    private $container;
    private $params = [];

    private $template = "@FoodBlog/Default/index.html.twig";
    private $template_params = [];
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
        $request = $this->container->get('request');

        switch (count($this->params)) {
            case 1: //blog category view

                list($categorySlug) = $this->params;

                $categoryObj = $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogCategory')
                    ->findOneBy(['active' => 1, 'language' => $request->getLocale(), 'slug' => $categorySlug]);

                if ($categoryObj) {

                    $blogPosts = $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogPost')
                        ->getAllPostsByCategory($categoryObj);

                    $this->template = "@FoodBlog/Hook/category.html.twig";
                    $this->template_params = ['category' => $categoryObj, 'blogPosts' => $blogPosts];
                } else {
                    throw new NotFoundHttpException();
                }

                break;
            case 0: //front blog view
                $categories = $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogCategory')
                    ->findBy(['active' => 1, 'language' => $request->getLocale()]);

                $blogPosts = [];
                foreach ($categories as $category) {
                    $blogPosts[$category->getId()] = $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogPost')
                        ->getTopThreePostsByCategory($category);
                }

                $this->template = "@FoodBlog/Hook/index.html.twig";
                $this->template_params = ['categories' => $categories, 'blogPosts' => $blogPosts];
                break;
            default: // blog post view
                if(count($this->params) >= 3){
                    throw new NotFoundHttpException();
                } else {
                    list($categorySlug, $postSlug,) = $this->params;
                    $post = $this->container->get('doctrine')->getRepository('FoodBlogBundle:BlogPost')->getBySlug($postSlug);
                    if ($post) {
                        $this->template = "@FoodBlog/Hook/post.html.twig";
                        $this->template_params = ['blogPost' => $post];
                    } else {
                        throw new NotFoundHttpException();
                    }
                }
                break;
        }

    }

    public function build()
    {



        $page_blog = $this->container->get('food.app.utils.misc')->getParam('page_blog');

        if($page_blog == 0) {
            throw new NotFoundHttpException();
        }
        $this->template_params['address'] = $this->container->get('slug')->getUrl($page_blog, 'page');

        return ['template' => $this->template, 'params' => $this->template_params];
    }

}
