<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeFormatter;
use App\Utility\PasscodeGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SurveyPasscodeController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys/{surveyId}/reveal-passcode", name="admin_domestic_survey_reveal_passcode")
     * @Template("admin/domestic/reveal_passcode/index.html.twig")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="survey")
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function revealPasscode(PasscodeGenerator $passcodeGenerator, Survey $survey): array
    {
        return [
            'survey' => $survey,
            'code' => PasscodeFormatter::formatPasscode($passcodeGenerator->getPasswordForUser($survey->getPasscodeUser())),
            'username' => PasscodeFormatter::formatPasscode($survey->getPasscodeUser()->getUsername()),
        ];
    }
}