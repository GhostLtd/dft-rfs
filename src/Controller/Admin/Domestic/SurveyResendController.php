<?php

namespace App\Controller\Admin\Domestic;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager, WorkflowInterface $domesticSurveyStateMachine) {
        parent::__construct($entityManager, $domesticSurveyStateMachine);
    }

    #[Route(path: '/csrgt/survey-resend/{id}', name: 'admin_domestic_survey_resend')]
    #[IsGranted(AdminSurveyVoter::RESEND, subject: 'survey')]
    public function resend(Request $request, Survey $survey): Response
    {
        return $this->doResend($request, $survey, AddSurveyType::class, 'admin/domestic/surveys/');
    }
}
