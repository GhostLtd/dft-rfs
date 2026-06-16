<?php

namespace App\Tests\NewFunctional\Wizard\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Admin\AbstractAdminTestCase;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;

class AddSurveyTest extends AbstractAdminTestCase
{
    public function testAddSurvey(): void
    {
        $this->initialiseTest([]);
        $this->assertEntityCountInDatabase(Survey::class, 0);

        $this->clickLinkContaining('Add Survey');

        $now = new \DateTime();
        $now->setTime(0, 0, 0);

        $regMark = 'AB01 ABC';

        $this->formTestAction('/csrgt/survey-add/', 'add_survey_submit', [
            new FormTestCase([
                'add_survey[registrationMark]' => $regMark,
                'add_survey[isNorthernIreland]' => '0',
                'add_survey[surveyPeriodStart][day]' => $now->format('d'),
                'add_survey[surveyPeriodStart][month]' => $now->format('m'),
                'add_survey[surveyPeriodStart][year]' => $now->format('Y'),
            ], [], null, true),
        ]);

        $this->assertDatabaseMatchesData([
            'registrationMark' => str_replace(' ', '', $regMark),
            'isNorthernIreland' => false,
            'surveyPeriodStart' => $now,
        ]);

        $this->assertPageTitleContains('CSRGT Survey created');
        $this->assertSummaryListData([
            'Registration' => $regMark,
            'Type' => 'CSRGT (GB)',
            'Survey period start' => $now->format('d/m/Y'),
        ]);
    }

    protected function assertDatabaseMatchesData(array $data): void
    {
        $this->callbackTestAction(function (Context $context) use ($data) {
            $test = $context->getTestCase();
            $entityManager = $context->getEntityManager();

            $repository = $entityManager->getRepository(Survey::class);

            $entityManager->clear();
            $surveys = $repository->findAll();

            $test->assertCount(1, $surveys, 'Expected a single survey to be in the database');

            $survey = $surveys[0];

            $this->assertDataMatches($survey, $data, 'survey');
        });
    }
}
