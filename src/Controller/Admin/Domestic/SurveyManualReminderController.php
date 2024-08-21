<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\SendReminderConfirmAction;
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
    #[Route(path: '/csrgt/manual-reminder/{id}', name: 'admin_domestic_survey_manual_reminder')]
    #[IsGranted(AdminSurveyVoter::MANUAL_REMINDER_BUTTON, subject: 'survey')]
    #[Template('admin/domestic/send_reminder/send-reminder.html.twig')]
    public function sendReminder(
        ManualReminderHelper      $reminderHelper,
        Request                   $request,
        Security                  $security,
        SendReminderConfirmAction $confirmAction,
        Survey                    $survey,
    ): array|Response
    {
        if (!$security->isGranted(AdminSurveyVoter::MANUAL_REMINDER, $survey)) {
            return $this->render('admin/domestic/send_reminder/unavailable.html.twig', [
                'reason' => $reminderHelper->getReasonWhyCannotSendManualReminder($survey),
                'survey' => $survey,
            ]);
        }

        return $confirmAction
            ->setSubject($survey)
            ->setExtraViewData([
                'recipients' => join(', ', $reminderHelper->getRecipientEmails($survey)),
            ])
            ->controller(
                $request,
                fn() => $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()])
            );
    }
}
