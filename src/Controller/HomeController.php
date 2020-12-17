<?php

namespace App\Controller;

use App\Form\GdsTestFormType;
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
     * @Route("/deploy-debug")
     * @param Request $request
     * @return Response
     */
    public function deploymentDebug(Request $request)
    {
        dump($request->query->all());
        if ($request->query->has('pi')) {
            phpinfo();
            exit;
        }

        $this->listFiles("/workspace/*");
        $this->listFiles("/workspace/config/*");

        exit;
        return $this->render('home/index.html.twig');
    }

    protected function listFiles($folder) {
        dump(glob($folder));
//        $list = glob($folder);
//        echo "<pre>\n";
//        foreach ($list as $file) {
//            echo "$file\n";
//        }
//        echo "</pre>\n";
    }
}
