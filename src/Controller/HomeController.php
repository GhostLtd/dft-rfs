<?php

namespace App\Controller;

use App\Controller\InternationalSurvey\IndexController as IntIndexController;
use App\Entity\PasscodeUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index()
    {
        $user = $this->getUser();
        if ($user && $user instanceof PasscodeUser) {
            if ($user->getDomesticSurvey()) {
                return $this->redirectToRoute('app_domesticsurvey_index');
            } else if ($user->getInternationalSurvey()) {
                return $this->redirectToRoute(IntIndexController::SUMMARY_ROUTE);
            }
        }

        return $this->render('home/index.html.twig');
    }

    /**
     * @Route("/sitemap.txt")
     */
    public function sitemap()
    {
        return $this->render('home/sitemap.txt.twig');
    }
}
