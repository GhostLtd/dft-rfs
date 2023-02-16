<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Admin\FlagQaConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveyFlagQaController extends AbstractController
{
    #[Route("/roro/surveys/{surveyId}/flag-qa", name: "admin_roro_surveys_flag_qa")]
    #[Entity("survey", expr: "repository.find(surveyId)")]
    #[Template("admin/roro/surveys/flag-qa.html.twig")]
    #[IsGranted(AdminSurveyVoter::FLAG_QA, subject: "survey")]
    public function flagQa(FlagQaConfirmAction $flagQaConfirmAction, Request $request, Survey $survey): Response|array
    {
        $flagQaConfirmAction
            ->setSubject($survey);

        return $flagQaConfirmAction->controller(
            $request,
            function() use ($survey) {
                return $this->generateUrl("admin_roro_surveys_view", ['surveyId' => $survey->getId()]);
            }
        );
    }
}