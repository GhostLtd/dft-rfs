<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210803134342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->remapApiResponses("domestic_survey");
        $this->remapApiResponses("international_survey");
        $this->remapApiResponses("pre_enquiry");
    }

    public function down(Schema $schema): void
    {
        $this->remapApiResponses("domestic_survey", true);
        $this->remapApiResponses("international_survey", true);
        $this->remapApiResponses("pre_enquiry", true);
    }

    protected function remapApiResponses(string $tableName, bool $reverse = false)
    {
        foreach(
            $this->connection->iterateAssociative("SELECT {$tableName}.id, {$tableName}.notify_api_responses FROM {$tableName}")
            as $result
        ) {
            $id = $result['id'];
            $apiResponses = $result['notify_api_responses'];

            if ($apiResponses !== null) {
                $apiResponses = json_decode($apiResponses, true);

                foreach($apiResponses as $k=>$v) {
                    if (!$reverse) {
                        $apiResponses[$k] = [$v];
                    } else {
                        // Retain the latest...
                        usort($v, fn($a, $b) => ($b['timestamp'] ?? null) <=> ($a['timestamp'] ?? null));
                        $apiResponses[$k] = reset($v);
                    }
                }

                $apiResponses = json_encode($apiResponses);

                $this->addSql("UPDATE ${tableName} SET notify_api_responses = :api_responses WHERE id = :id", [
                    'api_responses' => $apiResponses,
                    'id' => $id,
                ]);
            }
        }
    }
}
