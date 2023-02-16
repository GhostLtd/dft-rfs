<?php

namespace App\Workflow\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\SurveyInterface;
use App\Entity\SurveyStateInterface;
use App\Form\RoRo\IntroductionType;
use App\Form\RoRo\VehicleCountsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class RoRoState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_VEHICLE_COUNTS = 'vehicle-counts';
    const STATE_FINISH = 'finish';

    private Survey $subject;

    public function getSubject(): Survey
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (get_class($subject) !== Survey::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . Survey::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getStateFormMap(): array
    {
        return [
            self::STATE_INTRODUCTION => IntroductionType::class,
            self::STATE_VEHICLE_COUNTS => VehicleCountsType::class,
        ];
    }

    public function getStateTemplateMap(): array
    {
        return [
            self::STATE_INTRODUCTION => 'roro/survey/form-introduction.html.twig',
            self::STATE_VEHICLE_COUNTS => 'roro/survey/form-vehicle-counts.html.twig',
        ];
    }

    public function getDefaultTemplate(): ?string
    {
        return null;
    }

    public function isValidAlternativeStartState($state): bool
    {
        return match($state) {
            self::STATE_VEHICLE_COUNTS =>
                $this->subject->getState() !== SurveyStateInterface::STATE_NEW
                && $this->subject->getIsActiveForPeriod(),

            default => false
        };
    }
}