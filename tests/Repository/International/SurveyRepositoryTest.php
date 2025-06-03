<?php

namespace App\Tests\Repository\International;

use App\Entity\International\Survey;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\International\SurveyFixtures;
use App\Tests\Repository\AbstractRepositoryTest;

class SurveyRepositoryTest extends AbstractRepositoryTest
{
    public function dataGetSurveysForExport(): array
    {
        // survey dates: 2021-05-01 => 2021-05-10
        // survey end date uses less than comparison (not less than or equal)

        return [
            ['2021-04-29', '2021-04-30', false],
            ['2021-04-29', '2021-05-01', true],
            ['2021-04-29', '2021-05-12', true],
            ['2021-05-09', '2021-05-12', true],
            ['2021-05-10', '2021-05-11', false],
        ];
    }

    /**
     * @dataProvider dataGetSurveysForExport
     */
    public function testGetSurveysForExport(string $minDate, string $maxDate, bool $surveyExpected): void
    {
        $this->loadFixtures([SurveyFixtures::class]);
        $repo = $this->getRepository(Survey::class);
        $this->assertInstanceOf(SurveyRepository::class, $repo);

        $survey = $this->fixtureReferenceRepository->getReference('survey:int:simple', Survey::class);

        $result = $repo->getSurveysForExport(new \DateTime("$minDate midnight"), new \DateTime ("$maxDate midnight"));
        $this->assertCount($surveyExpected ? 1 : 0, $result);
        if ($surveyExpected) {
            $this->assertContains($survey, $result);
        }
    }
}
