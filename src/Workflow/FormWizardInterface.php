<?php


namespace App\Workflow;


interface FormWizardInterface
{
    public function getState();
    public function setState($state);

    public function getSubject();
    public function setSubject($subject);

    public function isValidAlternativeStartState($state): bool;
    public function isValidHistoryState($state): bool;
    public function addStateToHistory($state);
    public function getPreviousHistoryState(): ?string;

    public function getStateFormMap();
    public function getStateTemplateMap();
    public function getDefaultTemplate();
}