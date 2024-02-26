<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Tests\DataFixtures\International\ResponseStillActiveFixtures;
use App\Tests\NewFunctional\Wizard\Action\Context;

abstract class AbstractInitialDetailsTest extends AbstractSurveyTest
{
    protected function performChangeTest(int $linkIndex, \Closure $callback): void
    {
        $this->initialiseTest([ResponseStillActiveFixtures::class]);
        $this->pathTestAction('/international-survey');
        $this->clickLinkContaining('View/change the business');
        $this->pathTestAction('/international-survey/correspondence-and-business-details');

        $data = $this->getInitialData();

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);

        $this->clickLinkContaining('Change', $linkIndex);

        $callback($data);

        $this->assertDashboardMatchesData($data);
        $this->assertDatabaseMatchesData($data);
    }

    protected function getInitialData(): array
    {
        return [
            'contactName' => 'Tom',
            'contactEmail' => 'tom@example.com',
            'contactTelephone' => '0800 8118181',
            'numberOfEmployees' => '10-49',
            'activityStatus' => 'still-active',
            'businessNature' => 'Bread haulage',
            'annualInternationalJourneyCount' => 150,
        ];
    }

    protected function assertDashboardMatchesData(array $data): void
    {
        $expectedData = [
            'Name' => $data['contactName'],
            'Email' => $data['contactEmail'] ?? '-',
            'Phone' => $data['contactTelephone'] ?? '-',
            'international trips' => $data['annualInternationalJourneyCount'],
        ];

        if ($data['activityStatus'] === 'still-active') {
            $numberOfEmployees = join(' - ', explode('-', $data['numberOfEmployees']));

            $expectedData['Nature'] = $data['businessNature'];
            $expectedData['Number of employees'] = $numberOfEmployees;
        } else {
            $expectedData['Firm still performs'] = match($data['activityStatus']) {
                'ceased-trading' => 'No - firm has ceased trading',
                'only-domestic-work' => 'No - firm only carries out domestic work',
            };
        }

        $this->assertSummaryListData($expectedData);
    }

    protected function assertDatabaseMatchesData(array $data): void
    {
        $this->callbackTestAction(function (Context $context) use ($data) {
            $test = $context->getTestCase();
            $survey = $this->getSurvey($context->getEntityManager(), $test);

            $this->assertDataMatches($survey->getResponse(), $data, 'response');
        });
    }
}