<?php

namespace App\Messenger\AlphagovNotify;

class Sms extends AbstractSendMessage
{
    protected string $phoneNumber;
    protected ?string $senderId;

    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, string $phoneNumber, string $templateId, array $personalisation = [], ?string $reference = null, ?string $senderId = null)
    {
        parent::__construct($eventName, $originatingEntityClass, $originatingEntityId, $templateId, $phoneNumber, $personalisation, $reference);
        $this->phoneNumber = $phoneNumber;
        $this->senderId = $senderId;
    }

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