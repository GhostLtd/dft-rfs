<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Domestic\Survey;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Google\Cloud\Storage\Bucket;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210722103600 extends AbstractMigration
{
    private Bucket $exportBucket;

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $style = new SymfonyStyle(new StringInput(""), new ConsoleOutput());

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey ADD original_survey_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey ADD CONSTRAINT FK_C3309B36D96D2F88 FOREIGN KEY (original_survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3309B36D96D2F88 ON domestic_survey (original_survey_id)');

        $reissues = [];
        $missing = [];
        $multiple = [];

        $originalDiscards = $this->connection->fetchAllAssociative("SELECT domestic_survey.id, domestic_survey.is_northern_ireland, domestic_survey.survey_period_start, domestic_survey.registration_mark, domestic_survey.state, domestic_survey_response.is_in_possession_of_vehicle FROM domestic_survey LEFT JOIN domestic_survey_response ON domestic_survey.id = domestic_survey_response.survey_id WHERE domestic_survey_response.is_in_possession_of_vehicle IN('on-hire', 'sold')");
        foreach ($originalDiscards as $k=>$originalDiscard) {
            // missing check for start date, as there's one that has the wrong date!!
            // remove check for region (NI/GB), as some were reissued in wrong region
            // simpler just to find surveys with same reg, but not the same ID - some duplication (for multi-hires), but not a problem
            $result = $this->connection->fetchAllAssociative(
                "SELECT domestic_survey.id, domestic_survey.is_northern_ireland, domestic_survey.survey_period_start, domestic_survey.registration_mark, domestic_survey.state, domestic_survey_response.is_in_possession_of_vehicle FROM domestic_survey LEFT JOIN domestic_survey_response ON domestic_survey.id = domestic_survey_response.survey_id WHERE domestic_survey.id <> :id AND domestic_survey.registration_mark = :registration_mark AND domestic_survey.state NOT IN (:ignore1, :ignore2)" ,
                array_merge($originalDiscard, ['ignore1' => Survey::STATE_REJECTED, 'ignore2' => Survey::STATE_INVITATION_FAILED])
            );
            if (empty($result)) {
                $missing[] = "{$originalDiscard['registration_mark']} ({$originalDiscard['is_in_possession_of_vehicle']})";
            } else if (count($result) > 1) {
                $multiple[] = $originalDiscard['registration_mark'];
            } else {
                $reissued = $result[0];
                $this->addSql("UPDATE domestic_survey SET state = :state WHERE id = :id", ['state' => Survey::STATE_REISSUED, 'id' => $originalDiscard['id']]);
                $this->addSql("UPDATE domestic_survey SET original_survey_id = :originalId WHERE id = :id", ['originalId' => $originalDiscard['id'], 'id' => $reissued['id']]);

                $iterator = $this->exportBucket->objects([
                    'delimiter' => '/',
                    'prefix' => "csrgt-pdf/{$originalDiscard['registration_mark']}",
                ]);

                $reissues[] = [
                    "Reg mark" => $originalDiscard['registration_mark'],
                    "Pdf count" => count(iterator_to_array($iterator)),
                ];
            }
        }
        sort($missing);
        sort($multiple);
        usort($reissues, function ($a, $b) {return $a['Pdf count'] > $b['Pdf count'];});

        $style->section("Missing reissues");
        $style->listing($missing);

        $style->section("Multiple matches?!");
        $style->listing($multiple);

        $style->section("Matched reissues (rename PDFS)");
        $style->table(['Reg mark', 'PDF count'], $reissues);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_survey DROP FOREIGN KEY FK_C3309B36D96D2F88');
        $this->addSql('DROP INDEX UNIQ_C3309B36D96D2F88 ON domestic_survey');
        $this->addSql('ALTER TABLE domestic_survey DROP original_survey_id');

        $this->addSql("UPDATE domestic_survey SET state = :rejected WHERE state = :reissued", ['rejected' => Survey::STATE_REJECTED, 'reissued' => Survey::STATE_REISSUED]);
    }

    public function setExportBucket(Bucket $exportBucket)
    {
        $this->exportBucket = $exportBucket;
    }
}
