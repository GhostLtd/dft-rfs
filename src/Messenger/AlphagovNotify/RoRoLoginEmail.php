<?php

namespace App\Messenger\AlphagovNotify;

use App\Utility\AlphagovNotify\Reference;

class RoRoLoginEmail extends AbstractHighPrioMessage
{
    public function __construct(protected string $recipient, protected array $personalisation = [])
    {}

    public function getTemplateId(): string
    {
        return Reference::RORO_LOGIN_LINK;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getRecipientHash(): ?string
    {
        return $this->recipient ? hash('sha256', $this->recipient) : null;
    }

    public function getPersonalisation(): array
    {
        return $this->personalisation;
    }

    public function getSendMethodParameters(): array
    {
        return [
            $this->recipient,
            $this->getTemplateId(),
            $this->getPersonalisation(),
        ];
    }
}