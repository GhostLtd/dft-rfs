<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Controller\Admin\AbstractSurveyResendController;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\Admin\PreEnquiry\AddSurveyType;
use App\Repository\PasscodeUserRepository;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class PreEnquiryResendController extends AbstractSurveyResendController
{
    public function __construct(EntityManagerInterface $entityManager,
                                PasscodeUserRepository $passcodeUserRepository,
                                WorkflowInterface $preEnquiryStateMachine) {
        parent::__construct($entityManager, $passcodeUserRepository, $preEnquiryStateMachine);
    }

    /**
     * @Route("/pre-enquiry/resend/{id}", name="admin_pre_enquiry_resend")
     * @IsGranted(AdminSurveyVoter::RESEND, subject="preEnquiry")
     */
    public function resend(Request $request, PreEnquiry $preEnquiry): Response
    {
        return $this->doResend($request, $preEnquiry, AddSurveyType::class, 'admin/pre_enquiry/');
    }
}
