<?php

namespace App\Security\Voter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\HaulageSurveyInterface;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use App\Utility\Reminder\ManualReminderHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const EDIT_NOTES = 'EDIT_NOTES';
    const ENTER_BUSINESS_AND_VEHICLE_DETAILS = 'ENTER_BUSINESS_AND_VEHICLE_DETAILS';
    const FLAG_QA = 'FLAG_QA';
    const MANUAL_REMINDER = 'MANUAL_REMINDER';
    const MANUAL_REMINDER_BUTTON = 'MANUAL_REMINDER_BUTTON';
    const RESEND = 'RESEND';
    const RESET_PASSCODE = 'RESET_PASSCODE';
    const TRANSITION = 'TRANSITION';
    const VIEW_UNFILLED_REASON = 'VIEW_UNFILLED_REASON';

    private AuthorizationCheckerInterface $authorizationChecker;
    private ManualReminderHelper $manualReminderHelper;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ManualReminderHelper $manualReminderHelper)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->manualReminderHelper = $manualReminderHelper;
    }

    protected function supports(string $attribute, $subject): bool
    {
        $surveyInterfaceSupport = in_array($attribute, [self::EDIT, self::MANUAL_REMINDER, self::MANUAL_REMINDER_BUTTON, self::RESET_PASSCODE, self::TRANSITION, self::EDIT_NOTES, self::RESEND])
            && $subject instanceof SurveyInterface;

        $haulageInterfaceSupport = in_array($attribute, [self::FLAG_QA, self::VIEW_UNFILLED_REASON])
            && $subject instanceof HaulageSurveyInterface;

        $domesticOnlySupport = in_array($attribute, [self::ENTER_BUSINESS_AND_VEHICLE_DETAILS])
            && $subject instanceof DomesticSurvey;

        return $surveyInterfaceSupport || $haulageInterfaceSupport || $domesticOnlySupport;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN_USER')) {
            return false;
        }

        $openStatusesOrClosed = array_merge(SurveyInterface::ACTIVE_STATES, [
            SurveyInterface::STATE_CLOSED
        ]);

        $approvedOrExportedStatuses = [
            SurveyInterface::STATE_APPROVED,
            SurveyInterface::STATE_EXPORTING,
            SurveyInterface::STATE_EXPORTED,
        ];

        $notOpenStatuses = array_merge($approvedOrExportedStatuses, [
            SurveyInterface::STATE_CLOSED,
            SurveyInterface::STATE_REJECTED,
        ]);

        /** @var $subject SurveyInterface */
        $state = $subject->getState();

        switch ($attribute) {
            case self::EDIT:
            case self::RESET_PASSCODE:
                return in_array($state, $openStatusesOrClosed) && (!is_null($subject->getPasscodeUser()));
            case self::MANUAL_REMINDER_BUTTON:
                return $this->manualReminderHelper->canShowManualReminderButton($subject);
            case self::MANUAL_REMINDER:
                return $this->manualReminderHelper->canSendManualReminder($subject);
            case self::RESEND:
                return $state === SurveyInterface::STATE_INVITATION_FAILED;
            case self::VIEW_UNFILLED_REASON:
                if ($subject instanceof DomesticSurvey) {
                    $response = $subject->getResponse();

                    $isExemptVehicleType = $response?->getIsExemptVehicleType();
                    $isInPossessionOfVehicle = $response?->getIsInPossessionOfVehicle();

                    return in_array($state, $notOpenStatuses) &&
                        !$isExemptVehicleType &&
                        $isInPossessionOfVehicle === DomesticResponse::IN_POSSESSION_YES;
                } else if ($subject instanceof InternationalSurvey) {
                    return in_array($state, $notOpenStatuses) &&
                        $subject->shouldAskWhyEmptySurvey();
                }
                break;
            case self::TRANSITION:
            case self::EDIT_NOTES:
                return !in_array($state, [SurveyInterface::STATE_EXPORTING, SurveyInterface::STATE_EXPORTED]);
            case self::ENTER_BUSINESS_AND_VEHICLE_DETAILS:
                if (!$subject instanceof DomesticSurvey) {
                    return false;
                }

                $response = $subject->getResponse();

                return
                    in_array($state, $openStatusesOrClosed) &&
                    !$subject->isBusinessAndVehicleDetailsComplete() &&
                    $response?->getIsInPossessionOfVehicle() === DomesticResponse::IN_POSSESSION_YES &&
                    $response?->getIsExemptVehicleType() === DomesticResponse::IS_EXEMPT_NO;
            case self::FLAG_QA:
                assert($subject instanceof HaulageSurveyInterface);
                return $state === SurveyInterface::STATE_APPROVED && !$subject->getQualityAssured();
        }

        return false;
    }
}
