<?php

namespace App\Entity;

use App\Repository\NotifyCallbackRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A callback from GOV.UK Notify about a message we sent: a delivery receipt (email / SMS) or a returned letter.
 * Only callbacks that match one of our responses are stored.
 */
#[ORM\Entity(repositoryClass: NotifyCallbackRepository::class)]
#[ORM\Table(name: 'notify_callback')]
#[ORM\UniqueConstraint(name: 'uniq_notify_callback_notify_id_type_status', columns: ['notify_id', 'type', 'status'])]
class NotifyCallback
{
    use IdTrait;

    public const string TYPE_DELIVERY_RECEIPT = 'delivery-receipt';
    public const string TYPE_RETURNED_LETTER = 'returned-letter';

    public const string STATUS_DELIVERED = 'delivered';
    public const string STATUS_PERMANENT_FAILURE = 'permanent-failure';
    public const string STATUS_TEMPORARY_FAILURE = 'temporary-failure';
    public const string STATUS_TECHNICAL_FAILURE = 'technical-failure';
    // Returned-letter payloads have no status; a fixed one keeps them under the unique constraint (NULLs wouldn't be)
    public const string STATUS_RETURNED = 'returned';

    public const array DELIVERY_RECEIPT_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_PERMANENT_FAILURE,
        self::STATUS_TEMPORARY_FAILURE,
        self::STATUS_TECHNICAL_FAILURE,
    ];

    // Outcomes that mean the message didn't reach the recipient (temporary failures are shown, but aren't fatal)
    public const array FATAL_STATUSES = [
        self::STATUS_PERMANENT_FAILURE,
        self::STATUS_TECHNICAL_FAILURE,
        self::STATUS_RETURNED,
    ];

    #[ORM\ManyToOne(targetEntity: NotifyApiResponse::class, inversedBy: 'callbacks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?NotifyApiResponse $response = null;

    #[ORM\Column(type: Types::STRING, length: 36, options: ['fixed' => true])]
    private ?string $notifyId = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $status = null;

    /**
     * The callback as received, less the recipient ("to")
     */
    #[ORM\Column(type: Types::JSON)]
    private array $payload = [];

    /**
     * When Notify says it happened: completed_at for delivery receipts, date_sent for returned letters
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $occurredAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $receivedAt = null;

    public function isFatal(): bool
    {
        return in_array($this->status, self::FATAL_STATUSES, true);
    }

    public function getResponse(): ?NotifyApiResponse
    {
        return $this->response;
    }

    public function setResponse(NotifyApiResponse $response): self
    {
        $this->response = $response;

        // Keep the inverse side in step, so the response's outcome is right without a reload
        if (!$response->getCallbacks()->contains($this)) {
            $response->getCallbacks()->add($this);
        }

        return $this;
    }

    public function getNotifyId(): ?string
    {
        return $this->notifyId;
    }

    public function setNotifyId(string $notifyId): self
    {
        $this->notifyId = $notifyId;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): self
    {
        $this->payload = $payload;
        return $this;
    }

    public function getOccurredAt(): ?\DateTime
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(?\DateTime $occurredAt): self
    {
        $this->occurredAt = $occurredAt;
        return $this;
    }

    public function getReceivedAt(): ?\DateTime
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(\DateTime $receivedAt): self
    {
        $this->receivedAt = $receivedAt;
        return $this;
    }
}
