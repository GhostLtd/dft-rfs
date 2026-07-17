<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260717120000 extends AbstractMigration
{
    /** @var array<string, string> old FQCN => new (bundle) FQCN */
    private const CLASS_MAP = [
        'App\\Messenger\\AlphagovNotify\\Letter' => 'Ghost\\GovUkCoreBundle\\Messenger\\AlphagovNotify\\Letter',
        'App\\Messenger\\AlphagovNotify\\Email'  => 'Ghost\\GovUkCoreBundle\\Messenger\\AlphagovNotify\\Email',
    ];

    public function getDescription(): string
    {
        return 'Migrate notify_api_response.message_class from App FQCNs to the govuk-core-bundle FQCNs';
    }

    public function up(Schema $schema): void
    {
        foreach (self::CLASS_MAP as $old => $new) {
            $this->addSql(
                'UPDATE notify_api_response SET message_class = :new WHERE message_class = :old',
                ['new' => $new, 'old' => $old]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::CLASS_MAP as $old => $new) {
            $this->addSql(
                'UPDATE notify_api_response SET message_class = :old WHERE message_class = :new',
                ['old' => $old, 'new' => $new]
            );
        }
    }
}
