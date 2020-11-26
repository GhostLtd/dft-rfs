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
     * @Route(name="app_domesticsurvey_index")
     * @return Response
     */
    public function index(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        if (!$domesticSurvey->isInitialDetailsComplete()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }
        if (!$domesticSurvey->isBusinessAndVehicleDetailsComplete())
        {
            return $this->redirectToRoute("app_domesticsurvey_contactdetails");
        }

        // show summary
        return $this->render('domestic_survey/index.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }

    /**
     * @Route("/contact-and-business-details", name="app_domesticsurvey_contactdetails")
     * @return Response
     */
    public function contactAndBusinessDetails(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        if (!$domesticSurvey->isInitialDetailsComplete()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // show summary
        return $this->render('domestic_survey/contact-and-business-details.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }
}
