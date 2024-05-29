<?php

namespace App\Tests\Functional\Utility;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\Survey;
use App\Entity\Route\Route;
use App\Entity\SurveyStateInterface;
use App\Tests\DataFixtures\RoRo\OperatorRouteFixtures;

class RoRoDataDeleteTest extends AbstractSurveyDeleteTest
{
    public function dataDelete(): array
    {
        return [
            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2020
            ['2021-01-01 00:00:00', '2018-12-31 00:00:00', true],  // Q4 2018 should be deleted
            ['2021-01-01 00:00:00', '2019-01-01 00:00:00', false], // Q1 2019 should NOT be deleted

            // Q1 2021 -> Q1 2020
            //   Testing boundary of Q1 2021
            ['2021-01-01 00:00:00', '2018-10-01 00:00:00', true],  // This is Q1 2021, so Q4 2018 should be deleted
            ['2020-12-31 00:00:00', '2018-10-01 00:00:00', false], // This is Q4 2020, so Q4 2018 should NOT be deleted
        ];
    }

    protected function createSurveyFixture(\DateTime $date): Survey
    {
        $operator = $this->fixtureReferenceRepository->getReference('roro:operator:1', Operator::class);
        $route = $this->fixtureReferenceRepository->getReference('roro:route:1', Route::class);

        $survey = (new Survey())
            ->setSurveyPeriodStart($date)
            ->setOperator($operator)
            ->setRoute($route)
            ->setState(SurveyStateInterface::STATE_NEW);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        return $survey;
    }

    protected function surveyExists(string $surveyId): bool
    {
        return $this->entityManager
            ->getRepository(Survey::class)
            ->find($surveyId) !== null;
    }

    protected function getFixtures(): array
    {
        return [OperatorRouteFixtures::class];
    }
}
