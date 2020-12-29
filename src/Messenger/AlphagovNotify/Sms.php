<?php


namespace App\Messenger\AlphagovNotify;


class Sms extends AbstractMessage
{
    /**
     * @var string
     */
    protected $phoneNumber;

    /**
     * @var string
     */
    protected $senderId;

    /**
     * Email constructor.
     * @param $originatingEntityClass
     * @param $originatingEntityId
     * @param $address
     * @param $templateId
     * @param array $personalisation
     * @param null $reference
     * @param null $senderId
     */
    public function __construct($originatingEntityClass, $originatingEntityId, $address, $templateId, $personalisation = [], $reference = null, $senderId = null)
    {
        parent::__construct($originatingEntityClass, $originatingEntityId, $templateId, $personalisation, $reference);
        $this->phoneNumber = $address;
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

    /**
     * @return string
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     * @return self
     */
    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getSenderId(): ?string
    {
        return $this->senderId;
    }

    /**
     * @param string|null $senderId
     * @return self
     */
    public function setSenderId(?string $senderId): self
    {
        $this->senderId = $senderId;
    }
}