<?php

namespace App\Tests\Functional\Wizard;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CallbackSingleEntityDatabaseTestCase implements DatabaseTestCase
{
    protected string $singleEntityClass;
    protected $callback;

    public function __construct(string $singleEntityClass, callable $callback)
    {
        $this->singleEntityClass = $singleEntityClass;
        $this->callback = $callback;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void
    {
        $repo = $entityManager->getRepository($this->singleEntityClass);

        $entityManager->clear();
        $entities = $repo->findAll();

        $testCase::assertCount(1, $entities, "Expected a single {$this->singleEntityClass} to be in the database");
        ($this->callback)($entities[0], $entityManager, $testCase);
    }
}