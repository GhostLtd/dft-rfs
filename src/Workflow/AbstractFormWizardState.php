<?php


namespace App\Workflow;

abstract class AbstractFormWizardState implements FormWizardInterface
{
    protected $state;

    protected $stateHistory = [];

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    public function addStateToHistory($state)
    {
        $this->stateHistory[] = $state;
        return $this;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        if (in_array($state, $this->stateHistory)) {
            // split the history on the index (lose the remainder of the states)
            $this->stateHistory = array_slice($this->stateHistory, 0, array_search($state, $this->stateHistory));
        }

        $this->state = $state;
        return $this;
    }

    public function isValidHistoryState($state): bool
    {
        return in_array($state, $this->stateHistory);
    }

    public function isValidAlternativeStartState($state): bool
    {
        return false;
    }

    public function getPreviousHistoryState(): ?string
    {
        if (empty($this->stateHistory)) return null;
        return $this->stateHistory[array_key_last($this->stateHistory)];
    }
}