<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Utility\ConfirmAction\RoRo\SurveyCompleteConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveySubmitController extends AbstractController
{
    #[Route("/roro/survey/{surveyId}/complete", name: "app_roro_survey_complete")]
    #[Entity("survey", expr: "repository.find(surveyId)")]
    #[Security("is_granted('CAN_EDIT_RORO_SURVEY', survey)")]
    #[Template("roro/survey/complete-action.html.twig")]
    public function complete(
        Request $request,
        Survey $survey,
        SurveyCompleteConfirmAction $surveyCompleteConfirmAction,
    ): Response|array
    {
        $surveyCompleteConfirmAction
            ->setSubject($survey)
            ->setExtraViewData([
                'operator' => $survey->getOperator(),
                'survey' => $survey,
            ]);

        return $surveyCompleteConfirmAction->controller(
            $request,
            function() use ($survey) {
                return $this->generateUrl('app_roro_survey_view', [
                    'surveyId' => $survey->getId(),
                ]);
            }
        );
    }
}