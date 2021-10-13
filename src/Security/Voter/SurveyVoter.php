<?php

namespace App\Security\Voter;

use App\Entity\PasscodeUser;
use App\Entity\SurveyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SurveyVoter extends Voter
{
    const EDIT = 'EDIT';
    const VIEW_SUBMISSION_SUMMARY = 'VIEW_SUBMISSION_SUMMARY';

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::EDIT, self::VIEW_SUBMISSION_SUMMARY])
            && $subject instanceof SurveyInterface;
    }

    /**
     * @param string $attribute
     * @param SurveyInterface $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$this->authorizationChecker->isGranted(PasscodeUser::ROLE_PASSCODE_USER)) {
            return false;
        }

        switch ($attribute) {
            case self::EDIT:
                return in_array($subject->getState(), [
                    SurveyInterface::STATE_IN_PROGRESS,
                    SurveyInterface::STATE_NEW, SurveyInterface::STATE_INVITATION_SENT,
                    SurveyInterface::STATE_INVITATION_FAILED, SurveyInterface::STATE_INVITATION_PENDING
                ]);

            case self::VIEW_SUBMISSION_SUMMARY:
                return in_array($subject->getState(), [SurveyInterface::STATE_CLOSED, SurveyInterface::STATE_APPROVED]);
        }

        return false;
    }
}
