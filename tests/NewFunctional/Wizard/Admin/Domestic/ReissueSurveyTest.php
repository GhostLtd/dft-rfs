<?php

namespace App\Tests\NewFunctional\Wizard\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Tests\DataFixtures\Domestic\ResponseOnHireFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Admin\AbstractAdminTestCase;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use App\Utility\RegistrationMarkHelper;

class ReissueSurveyTest extends AbstractAdminTestCase
{
    public function testReissueSurvey(): void
    {
        // load fixture of submitted survey on hire
        $this->initialiseTest([ResponseOnHireFixtures::class]);
        $this->assertEntityCountInDatabase(Survey::class, 1);

        // view survey, click re-issue link
        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('survey:simple', Survey::class);
        $this->client->request('GET', "/csrgt/surveys/{$survey->getId()}");
        $this->assertPageTitleContains('CSRGT (NI) Survey');
        $this->clickLinkContaining('Reissue survey');
        $this->assertPageTitleContains('Add CSRGT survey');

        // submit re-issue form
        $this->formTestAction("/csrgt/survey-reissue/{$survey->getId()}", 'add_survey_submit', [
            new FormTestCase([
                'add_survey[invitationAddress][lines][line1]' => '',
            ], [], null, true),
        ]);

        // confirm two surveys
        $this->assertEntityCountInDatabase(Survey::class, 2);
        $this->assertPageTitleContains('CSRGT Survey created');
        $this->assertSummaryListData([
            'Registration' => (new RegistrationMarkHelper($survey->getRegistrationMark()))->getFormattedRegistrationMark(),
            'Type' => 'CSRGT (NI)',
            'Survey period start' => $survey->getSurveyPeriodStart()->format('d/m/Y'),
            'Invitation email' => $survey->getResponse()->getHireeEmail(),
        ]);
    }
}
