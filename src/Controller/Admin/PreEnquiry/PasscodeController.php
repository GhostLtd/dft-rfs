<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PasscodeController extends AbstractController
{
    /**
     * @Route("/pre-enquiry/{preEnquiryId}/reveal-passcode", name="admin_preenquiry_reveal_passcode")
     * @Template("admin/pre_enquiry/reveal_passcode/index.html.twig")
     * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="preEnquiry")
     */
    public function resetPasscodeSuccess(PasscodeGenerator $passcodeGenerator, PreEnquiry $preEnquiry): array
    {
        return [
            'preEnquiry' => $preEnquiry,
            'code' => $passcodeGenerator->getPasswordForUser($preEnquiry->getPasscodeUser()),
        ];
    }
}