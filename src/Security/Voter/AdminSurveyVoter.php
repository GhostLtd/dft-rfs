<?php

namespace App\Security\Voter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Domestic\SurveyResponse as DomesticResponse;
use App\Entity\HaulageSurveyInterface;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdminSurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const EDIT_NOTES = 'EDIT_NOTES';
    const ENTER_BUSINESS_AND_VEHICLE_DETAILS = 'ENTER_BUSINESS_AND_VEHICLE_DETAILS';
    const FLAG_QA = 'FLAG_QA';
    const RESEND = 'RESEND';
    const RESET_PASSCODE = 'RESET_PASSCODE';
    const TRANSITION = 'TRANSITION';
    const VIEW_UNFILLED_REASON = 'VIEW_UNFILLED_REASON';

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    protected function supports($attribute, $subject)
    {
        $surveyInterfaceSupport = in_array($attribute, [self::EDIT, self::RESET_PASSCODE, self::TRANSITION, self::EDIT_NOTES, self::RESEND])
            && $subject instanceof SurveyInterface;

        $haulageInterfaceSupport = in_array($attribute, [self::FLAG_QA, self::VIEW_UNFILLED_REASON])
            && $subject instanceof HaulageSurveyInterface;

        $domesticOnlySupport = in_array($attribute, [self::ENTER_BUSINESS_AND_VEHICLE_DETAILS])
            && $subject instanceof DomesticSurvey;

        return $surveyInterfaceSupport || $haulageInterfaceSupport || $domesticOnlySupport;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
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

        /** @var $subject SurveyInterface */
        $state = $subject->getState();

        switch ($attribute) {
            case self::EDIT:
            case self::RESET_PASSCODE:
                return in_array($state, $openStatusesOrClosed) && (!is_null($subject->getPasscodeUser()));
            case self::RESEND:
                return $state === SurveyInterface::STATE_INVITATION_FAILED;
            case self::VIEW_UNFILLED_REASON:
                if ($subject instanceof DomesticSurvey) {
                    return in_array($state, $approvedOrExportedStatuses);
                } else if ($subject instanceof InternationalSurvey) {
                    return in_array($state, array_merge($approvedOrExportedStatuses, [SurveyInterface::STATE_CLOSED]));
                }
                break;
            case self::TRANSITION:
            case self::EDIT_NOTES:
                return !in_array($state, [SurveyInterface::STATE_EXPORTING, SurveyInterface::STATE_EXPORTED]);
            case self::ENTER_BUSINESS_AND_VEHICLE_DETAILS:
                assert($subject instanceof DomesticSurvey);
                $response = $subject->getResponse();

                return in_array($state, $openStatusesOrClosed)
                    && !$subject->isBusinessAndVehicleDetailsComplete()
                    && $response
                    && $response->getIsInPossessionOfVehicle() === DomesticResponse::IN_POSSESSION_YES;
            case self::FLAG_QA:
                assert($subject instanceof HaulageSurveyInterface);
                return $state === SurveyInterface::STATE_APPROVED && !$subject->getQualityAssured();
        }

        return false;
    }
}
