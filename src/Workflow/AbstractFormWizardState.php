<?php


namespace App\Workflow;

abstract class AbstractFormWizardState implements FormWizardStateInterface
{
    protected $state;

    protected $stateHistory = [];

    /**
     * @return mixed
     */
    #[\Override]
    public function getState()
    {
        return $this->state;
    }

    #[\Override]
    public function addStateToHistory($state)
    {
        $this->stateHistory[] = $state;
        return $this;
    }

    /**
     * @param mixed $state
     */
    #[\Override]
    public function setState($state)
    {
        if (in_array($state, $this->stateHistory)) {
            // split the history on the index (lose the remainder of the states)
            $this->stateHistory = array_slice($this->stateHistory, 0, array_search($state, $this->stateHistory));
        }

        $this->state = $state;
        return $this;
    }

    #[\Override]
    public function isValidHistoryState($state): bool
    {
        return in_array($state, $this->stateHistory);
    }

    #[\Override]
    public function isValidAlternativeStartState($state): bool
    {
        return false;
    }

    #[\Override]
    public function getPreviousHistoryState(): ?string
    {
        if (empty($this->stateHistory)) return null;
        return $this->stateHistory[array_key_last($this->stateHistory)];
    }
}