<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeFormatter;
use App\Utility\PasscodeGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PasscodeController extends AbstractController
{
    #[Route(path: '/pre-enquiry/{preEnquiryId}/reveal-passcode', name: 'admin_preenquiry_reveal_passcode')]
    #[Template('admin/pre_enquiry/reveal_passcode/index.html.twig')]
    #[IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject: 'preEnquiry')]
    public function revealPasscode(
        PasscodeGenerator $passcodeGenerator,
        #[MapEntity(expr: "repository.find(preEnquiryId)")]
        PreEnquiry $preEnquiry,
    ): array
    {
        return [
            'preEnquiry' => $preEnquiry,
            'code' => PasscodeFormatter::formatPasscode($passcodeGenerator->getPasswordForUser($preEnquiry->getPasscodeUser())),
            'username' => PasscodeFormatter::formatPasscode($preEnquiry->getPasscodeUser()->getUserIdentifier()),
        ];
    }
}
