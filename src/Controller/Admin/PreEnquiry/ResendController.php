<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\Admin\PreEnquiry\AddSurveyType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator, WorkflowInterface $preEnquiryStateMachine) {
        parent::__construct($entityManager, $passcodeGenerator, $preEnquiryStateMachine);
    }

    /**
     * @Route("/pre-enquiry/{id}/resend", name="admin_preenquiry_resend")
     * @IsGranted(AdminSurveyVoter::RESEND, subject="preEnquiry")
     */
    public function resend(Request $request, PreEnquiry $preEnquiry): Response
    {
        return $this->doResend($request, $preEnquiry, AddSurveyType::class, 'admin/pre_enquiry/');
    }
}
