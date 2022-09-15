<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\LongAddress;
use App\Messenger\AlphagovNotify\Email;
use App\Messenger\AlphagovNotify\Letter;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220608133503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate NotifyApiResponse data over to the new entities';
    }

    /**
     * @throws \Exception
     */
    public function up(Schema $schema): void
    {
        foreach (['domestic_survey', 'international_survey', 'pre_enquiry'] as $tableName) {
            foreach ($this->connection->iterateAssociative("SELECT s.* FROM {$tableName} s") as $result) {
                $id = $result['id'];
                $apiResponseGroup = json_decode($result['notify_api_responses'], true);

                foreach ($apiResponseGroup as $key => $apiResponses) {
                    foreach ($apiResponses as $apiResponse) {
                        if (!preg_match('/^([a-z\d-]+)::(.+)$/', $key, $keyParts)) {
                            throw new \Exception('Unable to parse eventName::messageClass key');
                        }

                        [$_, $eventName, $messageClass] = $keyParts;
                        $timestamp = new \DateTime($apiResponse['timestamp']);

                        if ($messageClass === Letter::class) {
                            $address = (new LongAddress())
                                ->setLine1($result['invitation_address_line1'])
                                ->setLine2($result['invitation_address_line2'])
                                ->setLine3($result['invitation_address_line3'])
                                ->setLine4($result['invitation_address_line4'])
                                ->setLine5($result['invitation_address_line5'])
                                ->setLine6($result['invitation_address_line6'])
                                ->setPostcode($result['invitation_address_postcode']);

                            $recipient = $address->__toString();
                            $endPoint = '/v2/notifications/letter';
                        } else if ($messageClass === Email::class) {
                            // At this point in time, we only have a single email address in this field, so we can ignore
                            // that its name suggests otherwise!
                            $recipient = $result['invitation_emails'];
                            $endPoint = '/v2/notifications/email';
                        } else {
                            throw new \Exception('Unsupported class');
                        }

                        if (!$recipient) {
                            throw new \Exception('Unable to determine recipient');
                        }

                        $recipientHash = hash('sha256', $recipient);
                        $apiResponseId = $this->getUuid();

                        $this->addSql('INSERT INTO notify_api_response (id, event_name, message_class, recipient, recipient_hash, timestamp, data, endpoint) VALUES (:id, :event_name, :message_class, :recipient, :recipient_hash, :timestamp, :data, :endpoint)', [
                            'id' => $apiResponseId,
                            'event_name' => $eventName,
                            'message_class' => $messageClass,
                            'recipient' => $recipient,
                            'recipient_hash' => $recipientHash,
                            'timestamp' => $timestamp,
                            'data' => $apiResponse,
                            'endpoint' => $endPoint,
                        ], [
                            'data' => 'json',
                            'timestamp' => 'datetime',
                        ]);

                        $this->addSql("INSERT INTO {$tableName}_notify_api_responses SET survey_id=:survey_id, notify_api_response_id=:api_response_id", [
                            'survey_id' => $id,
                            'api_response_id' => $apiResponseId,
                        ]);
                    }
                }
            }
        }

        $this->addSql('ALTER TABLE domestic_survey DROP notify_api_responses');
        $this->addSql('ALTER TABLE international_survey DROP notify_api_responses');
        $this->addSql('ALTER TABLE pre_enquiry DROP notify_api_responses');

        $this->addSql('ALTER TABLE domestic_notification_interception CHANGE email emails VARCHAR(1024) NOT NULL');

    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey ADD notify_api_responses LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE international_survey ADD notify_api_responses LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE pre_enquiry ADD notify_api_responses LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:json)\'');

        $this->addSql('ALTER TABLE domestic_notification_interception CHANGE emails email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }

    protected function getUuid(): string
    {
        // TODO: This ties us to MySQL (and/or any platform sharing this syntax)
        //       Can be replaced with symfony/uid once we upgrade to Symfony 5.x
        return $this->connection->executeQuery('SELECT UUID()')->fetchOne();
    }
}
