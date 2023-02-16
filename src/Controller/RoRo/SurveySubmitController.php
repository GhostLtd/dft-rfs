<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Security\Voter\RoRo\SurveyVoter;
use App\Utility\ConfirmAction\RoRo\SurveyCompleteConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/roro/survey/{surveyId}", name: "app_roro_survey_")]
#[Entity("survey", expr: "repository.find(surveyId)")]
#[IsGranted(SurveyVoter::EDIT, "survey", statusCode: 403)]
class SurveySubmitController extends AbstractController
{
    #[Route("/complete", name: "complete")]
    #[Template("roro/survey/complete-action.html.twig")]
    public function complete(SurveyCompleteConfirmAction $surveyCompleteConfirmAction, Request $request, Survey $survey): Response|array
    {
        $surveyCompleteConfirmAction
            ->setSubject($survey);

        return $surveyCompleteConfirmAction->controller(
            $request,
            function() use ($survey) {
                return $this->generateUrl('app_roro_survey_view', ['surveyId' => $survey->getId()]);
            }
        );
    }
}