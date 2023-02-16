<?php

namespace App\Security\Voter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\HaulageSurveyInterface;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\QualityAssuranceInterface;
use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use App\Utility\Reminder\ManualReminderHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const EDIT_BOOKMARKS = 'EDIT_BOOKMARKS';
    const EDIT_NOTES = 'EDIT_NOTES';
    const ENTER_BUSINESS_AND_VEHICLE_DETAILS = 'ENTER_BUSINESS_AND_VEHICLE_DETAILS';
    const FLAG_QA = 'FLAG_QA';
    const MANUAL_REMINDER = 'MANUAL_REMINDER';
    const MANUAL_REMINDER_BUTTON = 'MANUAL_REMINDER_BUTTON';
    const RESEND = 'RESEND';
    const RESET_PASSCODE = 'RESET_PASSCODE';
    const TRANSITION = 'TRANSITION';
    const VIEW_UNFILLED_REASON = 'VIEW_UNFILLED_REASON';

    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected ManualReminderHelper $manualReminderHelper)
    {}

    protected function supports(string $attribute, $subject): bool
    {
        // RoRo, Pre-enquiry, CSRGT, IRHS
        $surveyStateInterfaceSupport = in_array($attribute, [self::EDIT_NOTES, self::TRANSITION])
            && $subject instanceof SurveyStateInterface;

        // Pre-enquiry, CSRGT, IRHS
        $surveyInterfaceSupport = in_array($attribute, [self::EDIT, self::MANUAL_REMINDER, self::MANUAL_REMINDER_BUTTON, self::RESET_PASSCODE, self::RESEND])
            && $subject instanceof SurveyInterface;

        // CSRGT, IRHS
        $haulageInterfaceSupport = $subject instanceof HaulageSurveyInterface && ($attribute == self::VIEW_UNFILLED_REASON);

        // RoRo, CSRGT, IRHS
        $qaInterfaceSupport = $subject instanceof QualityAssuranceInterface && ($attribute == self::FLAG_QA);

        // CSRGT
        $domesticOnlySupport = $subject instanceof DomesticSurvey && ($attribute === self::ENTER_BUSINESS_AND_VEHICLE_DETAILS);

        return $surveyStateInterfaceSupport || $surveyInterfaceSupport || $haulageInterfaceSupport || $qaInterfaceSupport || $domesticOnlySupport;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted('ROLE_ADMIN_USER')) {
            return false;
        }

        $openStatusesOrClosed = array_merge(SurveyStateInterface::ACTIVE_STATES, [
            SurveyStateInterface::STATE_CLOSED
        ]);

        $approvedOrExportedStatuses = [
            SurveyStateInterface::STATE_APPROVED,
            SurveyStateInterface::STATE_EXPORTING,
            SurveyStateInterface::STATE_EXPORTED,
        ];

        $notOpenStatuses = array_merge($approvedOrExportedStatuses, [
            SurveyStateInterface::STATE_CLOSED,
            SurveyStateInterface::STATE_REJECTED,
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
                return $state === SurveyStateInterface::STATE_INVITATION_FAILED;
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
                return !in_array($state, [SurveyStateInterface::STATE_EXPORTING, SurveyStateInterface::STATE_EXPORTED]);
            case self::ENTER_BUSINESS_AND_VEHICLE_DETAILS:
                if (!$subject instanceof DomesticSurvey) {
                    return false;
                }

                $response = $subject->getResponse();

                return
                    in_array($state, $openStatusesOrClosed) &&
                    !$subject->isBusinessAndVehicleDetailsComplete() &&
                    $response?->getIsInPossessionOfVehicle() === DomesticResponse::IN_POSSESSION_YES &&
                    !$response?->getIsExemptVehicleType();
            case self::FLAG_QA:
                assert($subject instanceof QualityAssuranceInterface);
                return $state === SurveyStateInterface::STATE_APPROVED && !$subject->getQualityAssured();
        }

        return false;
    }
}
