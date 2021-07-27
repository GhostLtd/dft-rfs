<?php

namespace App\Entity;

use DateTime;

interface HaulageSurveyInterface extends SurveyInterface
{
    public function getSurveyPeriodStart(): ?DateTime;
    public function setSurveyPeriodStart(?DateTime $surveyPeriodStart): self;

    public function getSurveyPeriodEnd(): ?DateTime;
    public function setSurveyPeriodEnd(?DateTime $surveyPeriodEnd): self;

    public function getSurveyPeriodStartModifiedBy($modifier);
    public function getSurveyPeriodInDays(): ?int;

    public function getQualityAssured(): ?bool;
    public function setQualityAssured(?bool $qualityAssured): self;
 }