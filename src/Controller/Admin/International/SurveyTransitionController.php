<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\International\Admin\FlagQaConfirmAction;
use App\Utility\ConfirmAction\International\Admin\SurveyWorkflowConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SurveyTransitionController extends AbstractController
{
    /**
     * @Route("/irhs/surveys/{surveyId}/transition/{transition}", name=SurveyController::TRANSITION_ROUTE,
     *     requirements={"transition": "complete|re_open|approve|reject|un_reject|un_approve"}
     * )
     * @Entity("survey", expr="repository.find(surveyId)")
     * @IsGranted(AdminSurveyVoter::TRANSITION, subject="survey")
     * @Template("admin/international/survey/workflow-action.html.twig")
     */
    public function complete(SurveyWorkflowConfirmAction $surveyWorkflowConfirmAction, Request $request, Survey $survey, $transition)
    {
        $surveyWorkflowConfirmAction
            ->setSubject($survey)
            ->setTransition($transition)
        ;
        return $surveyWorkflowConfirmAction->controller(
            $request,
            function() use ($survey) { return $this->generateUrl(
                SurveyController::VIEW_ROUTE,
                ['surveyId' => $survey->getId()]
            );
        });
    }

    /**
     * @Route("/irhs/surveys/{surveyId}/flag-qa", name=SurveyController::FLAG_QA_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     * @Template("admin/international/surveys/flag-qa.html.twig")
     * @IsGranted(AdminSurveyVoter::FLAG_QA, subject="survey")
     */
    public function flagQa(FlagQaConfirmAction $flagQaConfirmAction, Request $request, Survey $survey)
    {
        $flagQaConfirmAction
            ->setSubject($survey);

        return $flagQaConfirmAction->controller(
            $request,
            function() use ($survey) {
                return $this->generateUrl(
                    SurveyController::VIEW_ROUTE,
                    ['surveyId' => $survey->getId()]
                );
            }
        );
    }
}