<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260127121044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Trim NBSP and other Unicode whitespace from LCNI company names';
    }

    public function up(Schema $schema): void
    {
        // Find only names with leading/trailing NBSP or space (not all records)
        // NBSP in UTF-8 is 0xC2 0xA0, which is "Â " when interpreted as latin1
        $rows = $this->connection->fetchAllAssociative(<<<SQL
            SELECT id, name
            FROM notification_interception_company_name
            WHERE name REGEXP '^[[:space:]]|[[:space:]]$'
               OR name LIKE CONCAT(CHAR(194), CHAR(160), '%')
               OR name LIKE CONCAT('%', CHAR(194), CHAR(160))
        SQL);

        if (empty($rows)) {
            return;
        }

        // Also fetch all existing clean names to check for potential clashes
        $existingClean = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(name), id FROM notification_interception_company_name WHERE id NOT IN (' .
            implode(',', array_map(fn($r) => "'" . $r['id'] . "'", $rows)) . ')'
        );

        $toUpdate = [];
        $toDelete = [];

        foreach ($rows as $row) {
            $id = $row['id'];
            $original = $row['name'];

            // Normalize: collapse whitespace (including NBSP) and trim
            $clean = preg_replace('/[\s\x{00A0}]+/u', ' ', $original);
            $clean = mb_trim($clean);

            $cleanLower = strtolower($clean);

            if (isset($existingClean[$cleanLower])) {
                // Would clash with existing clean name - delete this one
                $toDelete[] = $id;
            } elseif (isset($toUpdate[$cleanLower])) {
                // Would clash with another dirty name we're updating - delete this one
                $toDelete[] = $id;
            } else {
                // Safe to update
                $toUpdate[$cleanLower] = ['id' => $id, 'name' => $clean];
            }
        }

        // Delete duplicates
        foreach ($toDelete as $id) {
            $this->addSql('DELETE FROM notification_interception_company_name WHERE id = ?', [$id]);
        }

        // Update dirty names
        foreach ($toUpdate as $data) {
            $this->addSql('UPDATE notification_interception_company_name SET name = ? WHERE id = ?', [$data['name'], $data['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        // Data-only migration - whitespace cannot be restored
    }
}
