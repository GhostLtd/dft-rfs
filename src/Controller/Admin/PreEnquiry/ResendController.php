<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\Admin\PreEnquiry\AddSurveyType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

class ResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager, WorkflowInterface $preEnquiryStateMachine) {
        parent::__construct($entityManager, $preEnquiryStateMachine);
    }

    #[Route(path: '/pre-enquiry/{id}/resend', name: 'admin_preenquiry_resend')]
    #[IsGranted(AdminSurveyVoter::RESEND, subject: 'preEnquiry')]
    public function resend(Request $request, PreEnquiry $preEnquiry): Response
    {
        return $this->doResend($request, $preEnquiry, AddSurveyType::class, 'admin/pre_enquiry/');
    }
}
