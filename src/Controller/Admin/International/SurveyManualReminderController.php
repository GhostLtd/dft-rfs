<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\SendReminderConfirmAction;
use App\Utility\LcniDisplayService;
use App\Utility\Reminder\ManualReminderHelper;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SurveyManualReminderController extends AbstractController
{
    public function __construct(
        protected LcniDisplayService $lcniDisplayService,
    ) {}

    #[Route(path: '/irhs/manual-reminder/{id}', name: 'admin_international_survey_manual_reminder')]
    #[IsGranted(AdminSurveyVoter::MANUAL_REMINDER_BUTTON, subject: 'survey')]
    #[Template('admin/international/send_reminder/send-reminder.html.twig')]
    public function sendReminder(
        ManualReminderHelper      $reminderHelper,
        Request                   $request,
        Security                  $security,
        SendReminderConfirmAction $confirmAction,
        Survey                    $survey,
    ): array|Response
    {
        if (!$security->isGranted(AdminSurveyVoter::MANUAL_REMINDER, $survey)) {
            return $this->render('admin/international/send_reminder/unavailable.html.twig', [
                'reason' => $reminderHelper->getReasonWhyCannotSendManualReminder($survey),
                'survey' => $survey,
            ]);
        }

        $hasResponseContactEmail = (bool) $survey->getResponseContactEmail();
        $recipientEmails = $reminderHelper->getRecipientEmails($survey);
        $lcniInfo = $this->lcniDisplayService->getDisplayInfoForSurvey($survey);

        return $confirmAction
            ->setSubject($survey)
            ->setExtraViewData([
                'hasResponseContactEmail' => $hasResponseContactEmail,
                'recipientEmails' => $recipientEmails,
                'lcniInfo' => $lcniInfo,
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])
            );
    }
}
