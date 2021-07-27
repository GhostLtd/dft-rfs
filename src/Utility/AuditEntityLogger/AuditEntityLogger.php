<?php

namespace App\Utility\AuditEntityLogger;

interface AuditEntityLogger
{
    public function getAuditLogEntries(array $changeSets, string $username): array;
    public function supports(string $className): bool;
}