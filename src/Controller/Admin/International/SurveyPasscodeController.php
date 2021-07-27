<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
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
 * @Route("/{surveyId}/reset-passcode")
 * @Entity("survey", expr="repository.find(surveyId)")
 * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="survey")
 */
class SurveyPasscodeController extends AbstractController
{
    /**
     * @Route("", name=SurveyController::RESET_PASSCODE_ROUTE)
     * @Template("admin/international/reset_passcode/reset.html.twig")
     */
    public function resetPasscode(ResetPasscodeConfirmAction $confirmAction, Request $request, Survey $survey, SessionInterface $session)
    {
        return $confirmAction
            ->setSubject($survey->getPasscodeUser())
            ->controller(
                $request,
                function() use ($confirmAction, $survey, $session) {
                    $session->set("reset-passcode.{$survey->getId()}", $confirmAction->getPasscode());
                    return $this->generateUrl(SurveyController::RESET_PASSCODE_SUCCESS_ROUTE, ['surveyId' => $survey->getId()]);
                },
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])
            );
    }


    /**
     * @Route("/success", name=SurveyController::RESET_PASSCODE_SUCCESS_ROUTE)
     * @Template("admin/international/reset_passcode/success.html.twig")
     */
    public function resetPasscodeSuccess(SessionInterface $session, Survey $survey): array
    {
        $key = "reset-passcode.{$survey->getId()}";
        $code = $session->remove($key);

        if (!$code) {
            throw new NotFoundHttpException();
        }

        return [
            'survey' => $survey,
            'code' => $code,
        ];
    }
}