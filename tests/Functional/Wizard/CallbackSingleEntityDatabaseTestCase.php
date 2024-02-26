<?php

namespace App\Tests\Functional\Wizard;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CallbackSingleEntityDatabaseTestCase implements DatabaseTestCase
{
    protected $callback;

    public function __construct(protected string $singleEntityClass, callable $callback)
    {
        $this->callback = $callback;
    }

    #[\Override]
    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void
    {
        $repo = $entityManager->getRepository($this->singleEntityClass);

        $entityManager->clear();
        $entities = $repo->findAll();

        $testCase::assertCount(1, $entities, "Expected a single {$this->singleEntityClass} to be in the database");
        ($this->callback)($entities[0], $entityManager, $testCase);
    }
}