<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\ResetPasscodeConfirmAction;
use App\Utility\PasscodeGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/pre-enquiry/view/{preEnquiryId}/reset-passcode")
 * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
 */
class PreEnquiryPasscodeController extends AbstractController
{
    /**
     * @Route("", name=PreEnquiryController::RESET_PASSCODE_ROUTE)
     * @Template("admin/pre_enquiry/reset_passcode/reset.html.twig")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="preEnquiry")
     */
    public function resetPasscode(ResetPasscodeConfirmAction $confirmAction, Request $request, PreEnquiry $preEnquiry, SessionInterface $session)
    {
        return $confirmAction
            ->setSubject($preEnquiry->getPasscodeUser())
            ->controller(
                $request,
                function() use ($confirmAction, $preEnquiry, $session) {
                    return $this->generateUrl(PreEnquiryController::RESET_PASSCODE_SUCCESS_ROUTE, ['preEnquiryId' => $preEnquiry->getId()]);
                },
                fn() => $this->generateUrl(PreEnquiryController::VIEW_ROUTE, ['preEnquiryId' => $preEnquiry->getId()])
            );
    }

    /**
     * @Route("/success", name=PreEnquiryController::RESET_PASSCODE_SUCCESS_ROUTE)
     * @Template("admin/pre_enquiry/reset_passcode/success.html.twig")
     */
    public function resetPasscodeSuccess(PasscodeGenerator $passcodeGenerator, PreEnquiry $preEnquiry): array
    {
        return [
            'preEnquiry' => $preEnquiry,
            'code' => $passcodeGenerator->getPasswordForUser($preEnquiry->getPasscodeUser()),
        ];
    }
}