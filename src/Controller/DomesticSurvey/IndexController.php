<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\PasscodeUser;
use App\Security\Voter\Domestic\SurveyVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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

        if (!$this->isGranted(SurveyVoter::EDIT, $domesticSurvey)) {
            $this->denyAccessUnlessGranted(SurveyVoter::VIEW_SUBMISSION_SUMMARY, $domesticSurvey);
            return $this->redirectToRoute("app_domesticsurvey_closed");
        }

        if (!$domesticSurvey->isInitialDetailsComplete()) {
            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // show summary
        return $this->render('domestic_survey/index.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }

    /**
     * @Route("/contact-and-business-details", name="app_domesticsurvey_contactdetails")
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
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

        if (!$domesticSurvey->isBusinessAndVehicleDetailsComplete()) {
            return $this->redirectToRoute('app_domesticsurvey_index');
        }

        // show summary
        return $this->render('domestic_survey/contact-and-business-details.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }

    /**
     * @return Response
     * @Security("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())")
     * @Route("/completed", name="app_domesticsurvey_closed")
     */
    public function completed(): Response
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $domesticSurvey = $user->getDomesticSurvey();

        // show summary
        return $this->render('domestic_survey/completed.html.twig', [
            'survey' => $domesticSurvey,
        ]);
    }
}
