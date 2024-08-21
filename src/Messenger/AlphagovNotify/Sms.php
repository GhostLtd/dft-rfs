<?php

namespace App\Messenger\AlphagovNotify;

class Sms extends AbstractSendMessage
{
    protected string $phoneNumber;

    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, string $phoneNumber, string $templateId, array $personalisation = [], ?string $reference = null, protected ?string $senderId = null)
    {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $phoneNumber, $personalisation, $reference);
        $this->phoneNumber = $phoneNumber;
    }

    #[\Override]
    public function getSendMethodParameters(): array
    {
        return [
            $this->phoneNumber,
            $this->getTemplateId(),
            $this->getPersonalisation(),
            $this->getReference(),
            $this->senderId,
        ];
    }

    #[\Override]
    public function getEndpoint(): string
    {
        return '/v2/notifications/sms';
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function getSenderId(): ?string
    {
        return $this->senderId;
    }
}