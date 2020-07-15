<?php

namespace App\Controller;

use App\Manager\ProductsManager;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends AbstractController
{
    private $manager;

    public function __construct(ProductsManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function index(Request $request)
    {
        $productList = $this->manager->getProducts($request);
        return $this->render('products/list.html.twig', ['list' => $productList]);
    }
}