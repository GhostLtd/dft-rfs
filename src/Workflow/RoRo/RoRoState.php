<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\SurveyStateInterface;
use App\Form\RoRo\CommentsType;
use App\Form\RoRo\DataEntryType;
use App\Form\RoRo\IntroductionType;
use App\Form\RoRo\VehicleCountsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class RoRoState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_DATA_ENTRY = 'data-entry';
    public const STATE_VEHICLE_COUNTS = 'vehicle-counts';
    public const STATE_COMMENTS = 'comments';
    public const STATE_FINISH = 'finish';

    private Survey $subject;
    private bool $hasWizardPreviouslyCompleted = false;

    #[\Override]
    public function getSubject(): Survey
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== Survey::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . Survey::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function determineWhetherWizardPreviouslyCompleted(): self
    {
        // This is called when the wizard gets started, and hence keeps a record
        // of whether the Survey had already been filled when the wizard was started.
        //
        // This is used for switching the flow of the transitions when first filling
        // the wizard vs when editing.
        //
        // Generally we'd use (id === null) for this, but that doesn't work for RoRo
        // surveys as they get initialised and saved to the database by the cron
        // *prior* to filling, so a RoRo survey *always* has a non-null ID.
        $this->hasWizardPreviouslyCompleted = ($this->subject->getIsActiveForPeriod() !== null);
        return $this;
    }

    public function hasWizardPreviouslyCompleted(): bool
    {
        return $this->hasWizardPreviouslyCompleted;
    }

    #[\Override]
    public function getStateFormMap(): array
    {
        return [
            self::STATE_INTRODUCTION => IntroductionType::class,
            self::STATE_DATA_ENTRY => DataEntryType::class,
            self::STATE_VEHICLE_COUNTS => VehicleCountsType::class,
            self::STATE_COMMENTS => CommentsType::class,
        ];
    }

    #[\Override]
    public function getStateTemplateMap(): array
    {
        return [
            self::STATE_INTRODUCTION => 'roro/survey/form-introduction.html.twig',
            self::STATE_DATA_ENTRY => 'roro/survey/form-data-entry.html.twig',
            self::STATE_VEHICLE_COUNTS => 'roro/survey/form-vehicle-counts.html.twig',
            self::STATE_COMMENTS => 'roro/survey/form-comments.html.twig',
        ];
    }

    #[\Override]
    public function getDefaultTemplate(): ?string
    {
        return null;
    }

    #[\Override]
    public function isValidAlternativeStartState($state): bool
    {
        return match($state) {
            self::STATE_DATA_ENTRY, self::STATE_VEHICLE_COUNTS =>
                $this->subject->getState() !== SurveyStateInterface::STATE_NEW &&
                $this->subject->getIsActiveForPeriod(),
            self::STATE_COMMENTS =>
                $this->subject->getState() !== SurveyStateInterface::STATE_NEW,
            default => false
        };
    }
}