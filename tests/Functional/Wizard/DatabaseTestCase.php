<?php

namespace App\Tests\Functional\Wizard;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

interface DatabaseTestCase
{
    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $testCase): void;
}