<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\RoRo\Admin\SurveyWorkflowConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/roro/surveys/{surveyId}", name: "admin_roro_survey_")]
class SurveyTransitionController extends AbstractController
{
    #[Route("/transition/{transition}", name: "transition", requirements: ["transition" => "complete|re_open|approve|reject|un_reject|un_approve"])]
    #[IsGranted(AdminSurveyVoter::TRANSITION, subject: "survey")]
    #[Template("admin/roro/surveys/workflow-action.html.twig")]
    public function complete(
        SurveyWorkflowConfirmAction $surveyWorkflowConfirmAction,
        Request $request,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        string $transition
    ): Response|array
    {
        $surveyWorkflowConfirmAction
            ->setSubject($survey)
            ->setTransition($transition);

        return $surveyWorkflowConfirmAction->controller(
            $request,
            fn() => $this->generateUrl('admin_roro_surveys_view', ['surveyId' => $survey->getId()])
        );
    }
}
