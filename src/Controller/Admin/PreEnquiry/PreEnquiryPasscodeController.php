<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\ResetPasscodeConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/pre-enquiry/{preEnquiryId}/reset-passcode")
 * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
 */
class PreEnquiryPasscodeController extends AbstractController
{
    /**
     * @Route("", name=PreEnquiryController::RESET_PASSCODE_ROUTE)
     * @Template("admin/pre-enquiry/reset_passcode/reset.html.twig")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="preEnquiry")
     */
    public function resetPasscode(ResetPasscodeConfirmAction $confirmAction, Request $request, PreEnquiry $preEnquiry, SessionInterface $session)
    {
        return $confirmAction
            ->setSubject($preEnquiry->getPasscodeUser())
            ->controller(
                $request,
                function() use ($confirmAction, $preEnquiry, $session) {
                    $session->set("reset-passcode.{$preEnquiry->getId()}", $confirmAction->getPasscode());
                    return $this->generateUrl(PreEnquiryController::RESET_PASSCODE_SUCCESS_ROUTE, ['preEnquiryId' => $preEnquiry->getId()]);
                },
                fn() => $this->generateUrl(PreEnquiryController::VIEW_ROUTE, ['preEnquiryId' => $preEnquiry->getId()])
            );
    }

    /**
     * @Route("/success", name=PreEnquiryController::RESET_PASSCODE_SUCCESS_ROUTE)
     * @Template("admin/pre-enquiry/reset_passcode/success.html.twig")
     */
    public function resetPasscodeSuccess(SessionInterface $session, PreEnquiry $preEnquiry): array
    {
        $key = "reset-passcode.{$preEnquiry->getId()}";
        $code = $session->remove($key);

        if (!$code) {
            throw new NotFoundHttpException();
        }

        return [
            'preEnquiry' => $preEnquiry,
            'code' => $code,
        ];
    }
}