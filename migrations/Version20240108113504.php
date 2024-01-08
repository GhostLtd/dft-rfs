<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240108113504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Pre-enquiry - remove approved state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE pre_enquiry SET state="closed" WHERE state="approved"');
    }

    public function down(Schema $schema): void
    {
    }
}
