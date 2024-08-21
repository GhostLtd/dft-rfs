<?php

namespace App\Tests\NewFunctional\Wizard\Action;

class CallbackAction extends AbstractAction
{
    protected $callback;
    protected ?string $description;
    protected ?string $descriptionCallback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
        $this->description = null;
        $this->descriptionCallback = null;
    }

    #[\Override]
    public function perform(Context $context): void
    {
        $this->outputHeader($context);
        $this->outputDebugDescription($context);

        $context->getEntityManager()->clear(); // Otherwise, we may get incorrect results to queries
        ($this->callback)($context);
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setDescriptionCallback(string $callback): self
    {
        $this->descriptionCallback = $callback;
        return $this;
    }

    public function getDescription(Context $context): ?string
    {
        return is_callable($this->descriptionCallback) ?
            ($this->descriptionCallback)($context) :
            $this->description;
    }

    protected function outputDebugDescription(Context $context): void
    {
        if ($context->isAtLeastDebugLevel(2)) {
            $description = $this->getDescription($context);

            if ($description) {
                $context->getOutput()->writeln("  -- <comment>{$description}</comment>\n");
            }
        }
    }
}