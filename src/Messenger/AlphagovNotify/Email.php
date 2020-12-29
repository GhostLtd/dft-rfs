<?php


namespace App\Messenger\AlphagovNotify;


class Email extends AbstractMessage
{
    /**
     * @var string
     */
    protected $emailAddress;

    /**
     * @var string
     */
    protected $emailReplyToId;

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
        $this->emailAddress = $address;
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

    /**
     * @return string
     */
    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    /**
     * @param string|null $emailAddress
     * @return self
     */
    public function setEmailAddress(?string $emailAddress): self
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string
     */
    public function getEmailReplyToId(): ?string
    {
        return $this->emailReplyToId;
    }

    /**
     * @param string|null $emailReplyToId
     * @return self
     */
    public function setEmailReplyToId(?string $emailReplyToId): self
    {
        $this->emailReplyToId = $emailReplyToId;
    }
}