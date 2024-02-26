<?php

namespace App\Controller\Admin\International;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\AddSurveyType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager, WorkflowInterface $internationalSurveyStateMachine) {
        parent::__construct($entityManager, $internationalSurveyStateMachine);
    }

    #[Route(path: '/irhs/survey-resend/{id}', name: 'admin_international_survey_resend')]
    #[IsGranted(AdminSurveyVoter::RESEND, subject: 'survey')]
    public function resend(Request $request, Survey $survey): Response
    {
        return $this->doResend($request, $survey, AddSurveyType::class, 'admin/international/surveys/');
    }
}
