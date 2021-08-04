<?php

namespace App\Controller\Admin\International;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\AddSurveyType;
use App\Repository\PasscodeUserRepository;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager,
                                PasscodeUserRepository $passcodeUserRepository,
                                WorkflowInterface $internationalSurveyStateMachine) {
        parent::__construct($entityManager, $passcodeUserRepository, $internationalSurveyStateMachine);
    }

    /**
     * @Route("/irhs/survey-resend/{id}", name="admin_international_survey_resend")
     * @IsGranted(AdminSurveyVoter::RESEND, subject="survey")
     */
    public function resend(Request $request, Survey $survey): Response
    {
        return $this->doResend($request, $survey, AddSurveyType::class, 'admin/international/surveys/');
    }
}
