<?php

namespace App\Tests\Utility\Notification;

use App\Entity\Domestic\SurveyNotification;
use App\Tests\Support\DeliveryProblemSurveysTrait;
use App\Utility\Notification\DeliveryProblems;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeliveryProblemsTest extends KernelTestCase
{
    use DeliveryProblemSurveysTrait;

    public function testCountsForSurveysInOneQuery(): void
    {
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $surveys = $this->createDeliveryProblemSurveys($entityManager);

        $counts = static::getContainer()->get(DeliveryProblems::class)
            ->countsForSurveys(SurveyNotification::class, array_map(fn($s) => $s->getId(), array_values($surveys)));

        $byRegMark = [];
        foreach ($surveys as $regMark => $survey) {
            $byRegMark[$regMark] = $counts[$survey->getId()] ?? ['count' => 0, 'unseen' => 0];
        }

        self::assertSame([
            'SENDFAIL1' => ['count' => 1, 'unseen' => 1],
            'SENDFAIL2' => ['count' => 1, 'unseen' => 0],
            'AFTER1' => ['count' => 1, 'unseen' => 1],
            'AFTER2' => ['count' => 1, 'unseen' => 0],
            'TEMP' => ['count' => 0, 'unseen' => 0],
            'OK' => ['count' => 0, 'unseen' => 0],
            'NONE' => ['count' => 0, 'unseen' => 0],
            'MIXED' => ['count' => 2, 'unseen' => 1],
        ], $byRegMark);
    }

    public function testCountsForNoSurveys(): void
    {
        self::assertSame([], static::getContainer()->get(DeliveryProblems::class)->countsForSurveys(SurveyNotification::class, []));
    }
}
