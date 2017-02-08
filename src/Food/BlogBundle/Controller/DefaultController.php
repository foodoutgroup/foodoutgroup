<?php

namespace Food\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction(Request $request)
    {
        $categories = $this->getDoctrine()->getRepository('FoodBlogBundle:BlogCategory')
            ->findBy(['active' => 1, 'language' => $request->getLocale()]);
        $blogPosts = [];
        foreach ($categories as $category) {
            $blogPosts[$category->getId()] = $this->getDoctrine()->getRepository('FoodBlogBundle:BlogPost')
                ->getTopThreePostsByCategory($category);
        }
        return $this->render(
            'FoodBlogBundle:Default:index.html.twig',
            ['categories' => $categories, 'blogPosts' => $blogPosts]
        );
    }

    public function categoryIndexAction($id)
    {
        $category = $this->getDoctrine()->getRepository('FoodBlogBundle:BlogCategory')->find($id);
        $blogPosts = $this->getDoctrine()->getRepository('FoodBlogBundle:BlogPost')->getAllPostsByCategory($category);
        return $this->render(
            'FoodBlogBundle:Default:category.html.twig',
            ['category' => $category, 'blogPosts' => $blogPosts]
        );
    }

    public function postIndexAction($id)
    {
        $blogPost = $this->getDoctrine()->getRepository('FoodBlogBundle:BlogPost')->find($id);
        return $this->render('FoodBlogBundle:Default:post.html.twig', ['blogPost' => $blogPost]);
    }
}