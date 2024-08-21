<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Admin\FlagQaConfirmAction;
use App\Utility\ConfirmAction\Domestic\Admin\SurveyWorkflowConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SurveyTransitionController extends AbstractController
{
    #[Route(path: '/csrgt/surveys/{surveyId}/transition/{transition}', name: SurveyController::TRANSITION_ROUTE, requirements: ['transition' => 'complete|re_open|approve|reject|un_reject|un_reject_exempt|un_approve'])]
    #[Template('admin/domestic/surveys/workflow-action.html.twig')]
    #[IsGranted(AdminSurveyVoter::TRANSITION, subject: 'survey')]
    public function transition(
        SurveyWorkflowConfirmAction $surveyWorkflowConfirmAction,
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        string $transition
    ): RedirectResponse|array
    {
        $surveyWorkflowConfirmAction
            ->setSubject($survey)
            ->setTransition($transition);

        return $surveyWorkflowConfirmAction->controller(
            $request,
            fn() => $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            )
        );
    }

    #[Route(path: '/csrgt/surveys/{surveyId}/flag-qa', name: SurveyController::FLAG_QA_ROUTE)]
    #[Template('admin/domestic/surveys/flag-qa.html.twig')]
    #[IsGranted(AdminSurveyVoter::FLAG_QA, subject: 'survey')]
    public function flagQa(
        FlagQaConfirmAction $flagQaConfirmAction,
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey
    ): Response|array
    {
        $flagQaConfirmAction
            ->setSubject($survey);

        return $flagQaConfirmAction->controller(
            $request,
            fn() => $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            )
        );
    }
}
