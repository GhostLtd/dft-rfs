<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait HaulageSurveyTrait {
    use SurveyTrait;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $qualityAssured;

    /**
     * N.B. The "add_survey" group is used by both international and domestic
     */
    #[Assert\NotNull(message: 'common.date.not-null', groups: ['add_survey', 'import_survey'])]
    #[AppAssert\GreaterThanOrEqualDate("midnight -14 weeks", groups: ['add_survey'])]
    #[AppAssert\GreaterThanOrEqualDate("midnight", groups: ['import_survey_non_historical'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $surveyPeriodStart = null;

    #[Assert\Expression('this.getSurveyPeriodEnd() >= this.getSurveyPeriodStart()', groups: ['add_international_survey'], message: 'common.survey.period-end.after-start')]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTime $surveyPeriodEnd = null;

    public function getSurveyPeriodStart(): ?DateTime
    {
        return $this->surveyPeriodStart;
    }

    public function setSurveyPeriodStart(?DateTime $surveyPeriodStart): self
    {
        $this->surveyPeriodStart = $surveyPeriodStart;
        return $this;
    }

    public function getSurveyPeriodStartModifiedBy(string $modifier): ?DateTime
    {
        if (is_null($this->surveyPeriodStart)) return null;
        $modifiedDate = (clone $this->surveyPeriodStart)->modify($modifier);
        return $modifiedDate === false ? null : $modifiedDate;
    }

    public function getSurveyPeriodEnd(): ?DateTime
    {
        return $this->surveyPeriodEnd;
    }

    public function setSurveyPeriodEnd(?DateTime $surveyPeriodEnd): self
    {
        $this->surveyPeriodEnd = $surveyPeriodEnd;
        return $this;
    }

    public function getSurveyPeriodInDays(): ?int
    {
        if (!$this->surveyPeriodEnd || !$this->surveyPeriodStart) {
            return null;
        }

        return $this->getSurveyPeriodEnd()->diff($this->surveyPeriodStart)->days + 1;
    }

    public function getQualityAssured(): ?bool
    {
        return $this->qualityAssured;
    }

    public function setQualityAssured(?bool $qualityAssured): self
    {
        $this->qualityAssured = $qualityAssured;
        return $this;
    }
}
