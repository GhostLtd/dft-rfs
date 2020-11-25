<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\PasscodeUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class IndexController
 * @package App\Controller\DomesticSurvey
 * @Route("/domestic-survey")
 */
class IndexController extends AbstractController
{
    /**
     * @Route(name="domestic_survey_index")
     * @return Response
     */
    public function index(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        if (!$domesticSurvey->getResponse()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // show summary
        return $this->render('domestic_survey/summary.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }
}
