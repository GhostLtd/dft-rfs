<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * View/function SQL originally in this migration has been moved to the `sql/` directory
 * and is applied by `bin/console app:views:apply`. See docs/Views.md.
 */
final class Version20260106124232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update view_export_international_survey: rename HGV field, add LGV journey count';
    }

    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
