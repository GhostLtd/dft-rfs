<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeFormatter;
use App\Utility\PasscodeGenerator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SurveyPasscodeController extends AbstractController
{
    #[Route(path: '/irhs/surveys/{surveyId}/reveal-passcode', name: 'admin_international_survey_reveal_passcode')]
    #[Template('admin/international/reveal_passcode/index.html.twig')]
    #[IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject: 'survey')]
    public function revealPasscode(
        PasscodeGenerator $passcodeGenerator,
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey            $survey
    ): array
    {
        return [
            'survey' => $survey,
            'code' => PasscodeFormatter::formatPasscode($passcodeGenerator->getPasswordForUser($survey->getPasscodeUser())),
            'username' => PasscodeFormatter::formatPasscode($survey->getPasscodeUser()->getUserIdentifier()),
        ];
    }
}
