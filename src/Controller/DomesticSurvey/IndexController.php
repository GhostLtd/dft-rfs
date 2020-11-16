<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\DomesticSurvey;
use App\Entity\DomesticSurveyResponse;
use App\Entity\DomesticVehicle;
use App\Workflow\DomesticSurveyInitialDetailsState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/domestic-survey", name="domestic_survey_index")
     * @return Response
     */
    public function index(): Response
    {
        $domesticSurveys = $this->getDoctrine()->getRepository(DomesticSurvey::class)->findAll();

        if (count($domesticSurveys) === 0) {
            // create one
            $domesticSurvey = (new DomesticSurvey())
                ->setIsNorthernIreland(false)
                ->setRegistrationMark("AA19PPP")
                ->setReminderState(DomesticSurvey::REMINDER_STATE_NOT_WANTED)
                ->setPasscode("1234")
            ;
            $em = $this->getDoctrine()->getManager();
            $vehicle = (new DomesticVehicle())->setRegistrationMark($domesticSurvey->getRegistrationMark());
            $surveyResponse = (new DomesticSurveyResponse())->setSurvey($domesticSurvey)->setVehicle($vehicle);
            $em->persist($surveyResponse);
            $em->flush();

            // start wizard
            return $this->redirectToRoute("app_domesticsurvey_initialdetails_start");
        }

        // take first one
        $domesticSurvey = array_pop($domesticSurveys);

        // show summary
        return $this->render('domestic_survey/summary.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }
}
