<?php

namespace App\Security\Voter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SurveyVoter extends Voter
{
    public const ELIGIBLE_TO_FILL_SURVEY_WEEK = "ELIGIBLE_TO_FILL_SURVEY_WEEK";
    public const CLOSE_SURVEY_EXEMPT = 'CLOSE_SURVEY_EXEMPT';
    public const CLOSE_SURVEY_NOT_IN_POSSESSION = 'CLOSE_SURVEY_NOT_IN_POSSESSION';
    public const EDIT = 'EDIT';
    public const CLOSING_DETAILS = 'CLOSING_DETAILS';
    public const PROVIDE_FEEDBACK = "PROVIDE_FEEDBACK";
    public const VIEW_FEEDBACK_SUMMARY = "VIEW_FEEDBACK_SUMMARY";
    public const VIEW_SUBMISSION_SUMMARY = 'VIEW_SUBMISSION_SUMMARY';

    public function __construct(private AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [
            self::CLOSE_SURVEY_EXEMPT,
            self::CLOSE_SURVEY_NOT_IN_POSSESSION,
            self::EDIT,
            self::CLOSING_DETAILS,
            self::ELIGIBLE_TO_FILL_SURVEY_WEEK,
            self::PROVIDE_FEEDBACK,
            self::VIEW_FEEDBACK_SUMMARY,
            self::VIEW_SUBMISSION_SUMMARY,
        ]) && $subject instanceof SurveyInterface;
    }

    /**
     * @param SurveyInterface $subject
     */
    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted(PasscodeUser::ROLE_PASSCODE_USER)) {
            return false;
        }

        $hasFeedback = fn(SurveyInterface $s) => $s->getFeedback() && $s->getFeedback()->getId();

        return match ($attribute) {
            self::CLOSE_SURVEY_EXEMPT =>
                $subject instanceof DomesticSurvey &&
                $subject->getState() === SurveyStateInterface::STATE_IN_PROGRESS &&
                $subject->getResponse()?->getIsExemptVehicleType() === true,

            self::CLOSE_SURVEY_NOT_IN_POSSESSION =>
                $subject instanceof DomesticSurvey &&
                $subject->getState() === SurveyStateInterface::STATE_IN_PROGRESS &&
                $subject->getResponse()?->isSoldScrappedStolenOrOnHire(),

            // Closing details and viewing the main survey week-filling screen is available to those who:
            // a) Have filled out initial-details
            // b) Are in possession of the vehicle
            // c) Don't have an exempt vehicle
            self::CLOSING_DETAILS,
            self::ELIGIBLE_TO_FILL_SURVEY_WEEK =>
                $subject instanceof DomesticSurvey &&
                $subject->getState() === SurveyStateInterface::STATE_IN_PROGRESS &&
                $subject->getResponse()?->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES &&
                $subject->isBusinessAndVehicleDetailsComplete() &&
                !$subject->getResponse()?->getIsExemptVehicleType(),

            self::EDIT => in_array($subject->getState(), [
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_NEW, SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_INVITATION_FAILED, SurveyStateInterface::STATE_INVITATION_PENDING
            ]),

            self::PROVIDE_FEEDBACK => in_array($subject->getState(), [SurveyStateInterface::STATE_CLOSED, SurveyStateInterface::STATE_APPROVED]) && !$hasFeedback($subject),

            self::VIEW_FEEDBACK_SUMMARY => in_array($subject->getState(), [SurveyStateInterface::STATE_CLOSED, SurveyStateInterface::STATE_APPROVED]) && $hasFeedback($subject),

            self::VIEW_SUBMISSION_SUMMARY => in_array($subject->getState(), [SurveyStateInterface::STATE_CLOSED, SurveyStateInterface::STATE_APPROVED, SurveyStateInterface::STATE_REJECTED]),

            default => false,
        };

    }
}
