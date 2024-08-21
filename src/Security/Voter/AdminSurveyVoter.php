<?php

namespace App\Security\Voter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\HaulageSurveyInterface;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\QualityAssuranceInterface;
use App\Entity\RoRo\Survey as RoroSurvey;
use App\Entity\SurveyInterface;
use App\Entity\SurveyManualReminderInterface;
use App\Entity\SurveyStateInterface;
use App\Utility\Reminder\ManualReminderHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSurveyVoter extends Voter
{
    public const EDIT = 'EDIT';
    public const EDIT_NOTES = 'EDIT_NOTES';
    public const ENTER_BUSINESS_AND_VEHICLE_DETAILS = 'ENTER_BUSINESS_AND_VEHICLE_DETAILS';
    public const ENTER_INITIAL_DETAILS = 'ENTER_INITIAL_DETAILS';
    public const FLAG_QA = 'FLAG_QA';
    public const MANUAL_REMINDER = 'MANUAL_REMINDER';
    public const MANUAL_REMINDER_BUTTON = 'MANUAL_REMINDER_BUTTON';
    public const RESEND = 'RESEND';
    public const RESET_PASSCODE = 'RESET_PASSCODE';
    public const TRANSITION = 'TRANSITION';
    public const VIEW_UNFILLED_REASON = 'VIEW_UNFILLED_REASON';

    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected ManualReminderHelper $manualReminderHelper)
    {}

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        // RoRo, Pre-enquiry, CSRGT, IRHS
        $surveyStateInterfaceSupport = in_array($attribute, [self::EDIT, self::EDIT_NOTES, self::TRANSITION])
            && $subject instanceof SurveyStateInterface;

        // Pre-enquiry, CSRGT, IRHS
        $manuallyReminderSupport = in_array($attribute, [self::MANUAL_REMINDER, self::MANUAL_REMINDER_BUTTON])
            && $subject instanceof SurveyManualReminderInterface;

        $surveyInterfaceSupport = in_array($attribute, [self::RESET_PASSCODE, self::RESEND])
            && $subject instanceof SurveyInterface;

        // CSRGT, IRHS
        $haulageInterfaceSupport = $subject instanceof HaulageSurveyInterface && ($attribute == self::VIEW_UNFILLED_REASON);

        // CSRGT, IRHS
        $qaInterfaceSupport = $subject instanceof QualityAssuranceInterface && ($attribute == self::FLAG_QA);

        // CSRGT
        $domesticOnlySupport = $subject instanceof DomesticSurvey && in_array($attribute, [self::ENTER_BUSINESS_AND_VEHICLE_DETAILS, self::ENTER_INITIAL_DETAILS]);

        return $surveyStateInterfaceSupport || $surveyInterfaceSupport || $manuallyReminderSupport || $haulageInterfaceSupport || $qaInterfaceSupport || $domesticOnlySupport;
    }

    #[\Override]
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

        $state = $subject->getState();

        if ($subject instanceof RoroSurvey && $attribute === self::EDIT) {
            return in_array($state, $openStatusesOrClosed);
        }

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
            case self::ENTER_INITIAL_DETAILS:
                if (!$subject instanceof DomesticSurvey) {
                    return false;
                }

                return
                    in_array($state, $openStatusesOrClosed) &&
                    !$subject->getResponse();
            case self::FLAG_QA:
                assert($subject instanceof QualityAssuranceInterface);
                return $state === SurveyStateInterface::STATE_APPROVED && !$subject->getQualityAssured();
        }

        return false;
    }
}
