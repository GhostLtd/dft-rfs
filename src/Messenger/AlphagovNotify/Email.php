<?php

namespace App\Messenger\AlphagovNotify;

class Email extends AbstractSendMessage
{
    protected string $emailAddress;
    protected ?string $emailReplyToId;

    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, string $emailAddress, string $templateId, array $personalisation = [], ?string $reference = null, ?string $senderId = null)
    {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $emailAddress, $personalisation, $reference);
        $this->emailAddress = $emailAddress;
        $this->emailReplyToId = $senderId;
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->emailAddress,
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
            $this->emailReplyToId,
        ];
    }

    public function getEndpoint(): string
    {
        return '/v2/notifications/email';
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getEmailReplyToId(): ?string
    {
        return $this->emailReplyToId;
    }
}