<?php


namespace App\Messenger\AlphagovNotify;

use App\Messenger\AbstractAsyncMessage;
use App\Utility\AlphagovNotify\Reference;

abstract class AbstractMessage extends AbstractAsyncMessage
{
    protected ?string $originatingEntityClass;
    protected ?string $originatingEntityId;
    private ?string $templateId;
    private ?array $personalisation;
    private ?string $reference;
    private ?string $eventName;

    public function __construct(string $eventName, string $originatingEntityClass, string $originatingEntityId, string $templateId, $personalisation = [], ?string $reference = null)
    {
        $this->originatingEntityClass = $originatingEntityClass;
        $this->originatingEntityId = $originatingEntityId;
        $this->templateId = $templateId;
        $this->personalisation = $personalisation;
        if (empty($reference)) {
            $this->reference = "{$originatingEntityClass}::{$originatingEntityId}";
        } else {
            $this->reference = $reference;
        }
        $this->eventName = $eventName;
    }

    abstract public function getSendMethodParameters(): array;

    /**
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * @param ?string $templateId
     * @return self
     */
    public function setTemplateId(?string $templateId): self
    {
        $this->templateId = $templateId;
    }

    /**
     * @return array
     */
    public function getPersonalisation(): ?array
    {
        return $this->personalisation;
    }

    /**
     * @param ?array $personalisation
     * @return self
     */
    public function setPersonalisation(?array $personalisation): self
    {
        $this->personalisation = $personalisation;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getReference(): ?string
    {
        return $this->reference;
    }

    /**
     * @param ?string $reference
     * @return self
     */
    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getOriginatingEntityClass(): ?string
    {
        return $this->originatingEntityClass;
    }

    public function getOriginatingEntityId(): ?string
    {
        return $this->originatingEntityId;
    }

    /**
     * @return string|null
     */
    public function getEventName(): ?string
    {
        return $this->eventName;
    }

    /**
     * @return false|string
     */
    public function getTemplateReferenceConstantName()
    {
        $templateReferenceReflection = new \ReflectionClass(Reference::class);
        return array_search($this->getTemplateId(), $templateReferenceReflection->getConstants());

    }
}