<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * View/function SQL originally in this migration has been moved to the `sql/` directory
 * and is applied by `bin/console app:views:apply`. See docs/Views.md.
 */
final class Version20260224150142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add State, IsNorthernIreland, YearNumber, WeekNumber, BusinessName, BusinessNature, NumberOfEmployees to driver availability export view';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
