<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Domestic\Admin\SurveyWorkflowConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SurveyTransitionController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys/{surveyId}/transition/{transition}", name=SurveyController::TRANSITION_ROUTE,
     *     requirements={"transition": "complete|re_open|approve|reject|un_reject|un_approve"}
     * )
     * @Entity("survey", expr="repository.find(surveyId)")
     * @Template("admin/domestic/surveys/workflow-action.html.twig")
     * @IsGranted(AdminSurveyVoter::TRANSITION, subject="survey")
     */
    public function transition(SurveyWorkflowConfirmAction $surveyWorkflowConfirmAction, Request $request, Survey $survey, $transition)
    {
        $surveyWorkflowConfirmAction
            ->setSubject($survey)
            ->setTransition($transition);

        return $surveyWorkflowConfirmAction->controller(
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