<?php


namespace App\Workflow;


interface FormWizardInterface
{
    public function getState();
    public function setState($state);

    public function getSubject();
    public function setSubject($subject);

//    public function getValidJumpInStates();
    public function isValidJumpInState($state);

    public function getStateFormMap();
    public function getStateTemplateMap();
    public function getDefaultTemplate();
}