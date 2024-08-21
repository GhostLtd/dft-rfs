<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\ConfirmAction\RoRo\SurveyCompleteConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

class SurveySubmitController extends AbstractController
{
    #[Route("/roro/survey/{surveyId}/complete", name: "app_roro_survey_complete")]
    #[IsGranted("CAN_EDIT_RORO_SURVEY", "survey")]
    #[Template("roro/survey/complete-action.html.twig")]
    public function complete(
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
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
            fn() => $this->generateUrl('app_roro_survey_view', [
                'surveyId' => $survey->getId(),
            ])
        );
    }
}
