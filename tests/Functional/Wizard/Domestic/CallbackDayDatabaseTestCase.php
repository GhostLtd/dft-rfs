<?php

namespace App\Tests\Functional\Wizard\Domestic;

use App\Entity\Domestic\Day;
use App\Tests\Functional\Wizard\DatabaseTestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CallbackDayDatabaseTestCase implements DatabaseTestCase
{
    protected int $dayNumber;
    protected $callback;

    public function __construct(int $dayNumber, callable $callback)
    {
        $this->dayNumber = $dayNumber;
        $this->callback = $callback;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void
    {
        $repo = $entityManager->getRepository(Day::class);

        $entityManager->clear();
        $entities = $repo->findBy(['number' => $this->dayNumber]);

        $testCase::assertCount(1, $entities, "Expected a single Day with number {$this->dayNumber} to be in the database");
        ($this->callback)($entities[0], $entityManager, $testCase);
    }
}