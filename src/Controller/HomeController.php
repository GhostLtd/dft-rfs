<?php

namespace App\Controller;

use App\Utility\Domestic\DataExporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/sitemap.txt")
     */
    public function sitemap()
    {
        return $this->render('home/sitemap.txt.twig');
    }

    /**
     * @Route("/testing")
     */
    public function test(DataExporter $dataExporter)
    {
        dump($dataExporter->getDataForExport(2020, 4));

        return $this->render('home/index.html.twig');
    }
}
