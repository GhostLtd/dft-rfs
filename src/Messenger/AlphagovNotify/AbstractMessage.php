<?php


namespace App\Messenger\AlphagovNotify;

use App\Messenger\AbstractAsyncMessage;

abstract class AbstractMessage extends AbstractAsyncMessage
{
    protected $originatingEntityClass;
    protected $originatingEntityId;

    /**
     * @var string
     */
    private $templateId;

    /**
     * @var array
     */
    private $personalisation;

    /**
     * @var string
     */
    private $reference;

    public function __construct($originatingEntityClass, $originatingEntityId, $templateId, $personalisation = [], $reference = null)
    {
        $this->originatingEntityClass = $originatingEntityClass;
        $this->originatingEntityId = $originatingEntityId;
        $this->templateId = $templateId;
        $this->personalisation = $personalisation;
        $this->reference = $reference;
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
     * @param string $templateId
     * @return self
     */
    public function setTemplateId(?string $templateId): self
    {
        $this->templateId = $templateId;
    }

    /**
     * @return array
     */
    public function getPersonalisation()
    {
        return $this->personalisation;
    }

    /**
     * @param array $personalisation
     * @return self
     */
    public function setPersonalisation(?array $personalisation): self
    {
        $this->personalisation = $personalisation;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string|null $reference
     * @return self
     */
    public function setReference(?string $reference): self
    {
        $this->reference = $reference;
    }
}