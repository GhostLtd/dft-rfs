<?php

namespace App\Tests\Functional\Utility;

use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Entity\SurveyInterface;
use App\Tests\DataFixtures\UserFixtures;
use App\Tests\Functional\AbstractFunctionalTest;
use App\Utility\Cleanup\SurveyDeleteUtility;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractSurveyDeleteTest extends AbstractFunctionalTest
{
    protected ?SurveyDeleteUtility $deleteUtility;
    protected ?EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deleteUtility = static::getContainer()->get(SurveyDeleteUtility::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    abstract public function dataDelete(): array;

    /**
     * @dataProvider dataDelete
     */
    public function testDelete(string $currentDate, string $surveyPeriodEnd, bool $expectedSurveyDeleted): void
    {
        $this->loadFixtures($this->getFixtures());

        $survey = $this->createSurveyFixture(new \DateTime($surveyPeriodEnd));
        $surveyId = $survey->getId();

        $this->deleteUtility
            ->setCurrentDateForTesting(new \DateTime($currentDate))
            ->deleteOldSurveys();


        $this->assertEquals(
            $expectedSurveyDeleted,
            !$this->surveyExists($surveyId),
            "Expected survey to".($expectedSurveyDeleted ? "" : " not")." be deleted"
        );
    }

    /**
     * @return \class-string[]
     */
    protected function getFixtures(): array
    {
        return [UserFixtures::class];
    }

    abstract protected function createSurveyFixture(\DateTime $date): SurveyInterface|RoRoSurvey;

    abstract protected function surveyExists(string $surveyId): bool;
}
