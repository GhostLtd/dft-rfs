<?php

namespace App\Tests\Entity\AuditLog;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey;
use PHPUnit\Framework\TestCase;

class AuditLogTest extends TestCase
{
    public function testEntityClassIsStoredAsTheMappedClassEvenForAProxy(): void
    {
        self::assertSame(Survey::class, (new AuditLog())->setEntityClass(Survey::class)->getEntityClass());
        self::assertSame(Survey::class, (new AuditLog())->setEntityClass('Proxies\\__CG__\\'.Survey::class)->getEntityClass());
    }
}
