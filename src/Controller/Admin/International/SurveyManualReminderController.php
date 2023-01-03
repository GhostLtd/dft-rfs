<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\Common\Admin\SendReminderConfirmAction;
use App\Utility\Reminder\ManualReminderHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class SurveyManualReminderController extends AbstractController
{
    /**
     * @Route("/irhs/manual-reminder/{id}", name="admin_international_survey_manual_reminder")
     * @IsGranted(AdminSurveyVoter::MANUAL_REMINDER_BUTTON, subject="survey")
     * @Template("admin/international/send_reminder/send-reminder.html.twig")
     */
    public function sendReminder(
        ManualReminderHelper $reminderHelper,
        Request $request,
        Security $security,
        SendReminderConfirmAction $confirmAction,
        Survey $survey,
    ): array|Response
    {
        if (!$security->isGranted(AdminSurveyVoter::MANUAL_REMINDER, $survey)) {
            return $this->render('admin/international/send_reminder/unavailable.html.twig', [
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
