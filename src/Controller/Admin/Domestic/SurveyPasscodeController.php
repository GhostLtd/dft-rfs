<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Security\Voter\Domestic\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\ResetPasscodeConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SurveyPasscodeController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys/{surveyId}/reset-passcode", name=SurveyController::RESET_PASSCODE_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     * @Template("admin/domestic/reset_passcode/reset.html.twig")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="survey")
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
}