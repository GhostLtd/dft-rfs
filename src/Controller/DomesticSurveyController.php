<?php

namespace App\Controller;

use App\Entity\DomesticSurvey;
use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DomesticSurveyController extends AbstractController
{
    /**
     * @Route("/domestic-survey", name="domestic_survey_index")
     */
    public function index(Request $request): Response
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
            $surveyResponse = (new DomesticSurveyResponse())->setSurvey($domesticSurvey);
            $em->persist($surveyResponse);
            $em->flush();

            $state = new DomesticSurveyState();
            $state->setSubject($surveyResponse);

            $request->getSession()->set(AbstractWorkflowController::SESSION_KEY, $state);

            // start wizard
            return $this->redirectToRoute("app_domesticinitialdetails_start");
        }

        // take first one
        $domesticSurvey = array_pop($domesticSurveys);

        // show summary
        return $this->render('domestic_survey/summary.html.twig', [
            'domesticSurvey' => $domesticSurvey,
        ]);
    }
}
