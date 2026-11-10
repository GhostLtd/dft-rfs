<?php

namespace App\Entity;

use App\Repository\NotifyApiResponseRepository;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotifyApiResponseRepository::class)]
#[ORM\Index(name: 'idx_notify_api_response_notify_id', columns: ['notify_id'])]
class NotifyApiResponse
{
    use IdTrait;

    #[ORM\Column(type: Types::STRING, length: 32)]
    private ?string $eventName = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $messageClass = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $endpoint = null;

    #[ORM\Column(type: Types::JSON)]
    private array $data = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $timestamp = null;

    /**
     * A hash of the recipient (i.e. address, sms or email -> unique string)
     */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $recipientHash = null;

    /**
     * The recipient as a string
     *
     * NOTE: We weren't sure which format to use, so are including both for the moment
     *
     *       These fields won't be necessary if we transition to Survey -> Invitation -> NotifyApiResponses,
     *       but that will take a while.
     */
    #[ORM\Column(type: Types::STRING, length: 1024, nullable: true)]
    private ?string $recipient = null;

    /**
     * Notify's own id for the notification (accepted responses only): what Notify's callbacks refer to
     */
    #[ORM\Column(type: Types::STRING, length: 36, nullable: true, options: ['fixed' => true])]
    private ?string $notifyId = null;

    /**
     * When an admin marked this response's delivery problem as seen (null = unseen, or no problem)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $problemSeenAt = null;

    /**
     * @var Collection<int, NotifyCallback>
     */
    #[ORM\OneToMany(targetEntity: NotifyCallback::class, mappedBy: 'response')]
    #[ORM\OrderBy(['receivedAt' => 'ASC'])]
    private Collection $callbacks;

    public function __construct()
    {
        $this->callbacks = new ArrayCollection();
    }

    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getMessageClass(): ?string
    {
        return $this->messageClass;
    }

    public function setMessageClass(string $messageClass): self
    {
        $this->messageClass = $messageClass;
        return $this;
    }

    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getData(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(\DateTime $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getRecipientHash(): ?string
    {
        return $this->recipientHash;
    }

    public function setRecipientHash(?string $recipientHash): self
    {
        $this->recipientHash = $recipientHash;
        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(?string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getNotifyId(): ?string
    {
        return $this->notifyId;
    }

    public function setNotifyId(?string $notifyId): self
    {
        $this->notifyId = $notifyId;
        return $this;
    }

    public function getProblemSeenAt(): ?\DateTime
    {
        return $this->problemSeenAt;
    }

    public function setProblemSeenAt(?\DateTime $problemSeenAt): self
    {
        $this->problemSeenAt = $problemSeenAt;
        return $this;
    }

    /**
     * @return Collection<int, NotifyCallback>
     */
    public function getCallbacks(): Collection
    {
        return $this->callbacks;
    }

    public function getStatus(): ?string
    {
        return $this->data[AlphagovNotifyMessengerSubscriber::STATUS_KEY] ?? null;
    }

    /**
     * Notify rejected the request, or sending failed in a way Messenger won't retry
     */
    public function isSendTimeFailure(): bool
    {
        return $this->getStatus() === Reference::STATUS_FAILED;
    }

    /**
     * Notify accepted it, but then reported a fatal outcome (permanent/technical failure, or a returned letter)
     */
    public function isAfterSendingFailure(): bool
    {
        return $this->callbacks->exists(fn(int $key, NotifyCallback $callback) => $callback->isFatal());
    }

    public function hasDeliveryProblem(): bool
    {
        return $this->isSendTimeFailure() || $this->isAfterSendingFailure();
    }

    public function isDeliveryProblemUnseen(): bool
    {
        return $this->hasDeliveryProblem() && $this->problemSeenAt === null;
    }
}
