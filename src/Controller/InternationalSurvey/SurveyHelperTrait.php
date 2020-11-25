<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\PasscodeUser;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

trait SurveyHelperTrait
{
    protected function getSurvey(UserInterface $user): Survey
    {
        $survey = ($user instanceof PasscodeUser) ? $user->getInternationalSurvey() : null;

        if (!$survey || !$survey instanceof Survey) {
            throw new UnauthorizedHttpException('No such survey');
        }

        return $survey;
    }
}