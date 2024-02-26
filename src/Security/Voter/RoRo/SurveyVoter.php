<?php

namespace App\Security\Voter\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\RoRoUser;
use App\Entity\SurveyStateInterface;
use App\Utility\RoRo\OperatorSwitchHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SurveyVoter extends Voter
{
    public const CAN_VIEW_RORO_DASHBOARD = 'CAN_VIEW_RORO_DASHBOARD';
    public const CAN_EDIT_RORO_SURVEY = 'CAN_EDIT_RORO_SURVEY';
    public const CAN_VIEW_RORO_SURVEY = 'CAN_VIEW_RORO_SURVEY';

    public function __construct(protected OperatorSwitchHelper $operatorSwitchHelper)
    {}

    #[\Override]
    protected function supports(string $attribute, $subject): bool
    {
        return (
            in_array($attribute, [self::CAN_EDIT_RORO_SURVEY, self::CAN_VIEW_RORO_SURVEY])
                && $subject instanceof Survey
        ) || (
            $attribute === self::CAN_VIEW_RORO_DASHBOARD &&
            $subject instanceof Operator
        );
    }


    #[\Override]
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof RoRoUser) {
            return false;
        }

        return match ($attribute) {
            self::CAN_VIEW_RORO_DASHBOARD =>
                $this->voteOnViewDashboard($subject, $user),

            self::CAN_EDIT_RORO_SURVEY,
            self::CAN_VIEW_RORO_SURVEY =>
                $this->voteOnEditOrViewSurvey($attribute, $subject, $user),

            default => false,
        };
    }

    protected function voteOnViewDashboard(Operator $operator, RoRoUser $user): bool
    {
        return $this->operatorSwitchHelper->canSwitchBetweenOperators($user->getOperator(), $operator);
    }

    protected function voteOnEditOrViewSurvey(string $attribute, Survey $survey, RoRoUser $user): bool
    {
        // Check that operator user is allowed to be accessing the survey's operator
        if (!$this->operatorSwitchHelper->canSwitchBetweenOperators($user->getOperator(), $survey->getOperator())) {
            return false;
        }

        return match($attribute)
        {
            self::CAN_EDIT_RORO_SURVEY => in_array($survey->getState(), [
                    SurveyStateInterface::STATE_IN_PROGRESS,
                    SurveyStateInterface::STATE_NEW,
                ]),

            self::CAN_VIEW_RORO_SURVEY => true,

            default => false
        };
    }
}