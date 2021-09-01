<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait HaulageSurveyTrait {
    use SurveyTrait;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private ?bool $qualityAssured;

    /**
     * @ORM\Column(type="date", nullable=true)
     * add_survey group is used by both international and domestic
     * @AppAssert\GreaterThanOrEqualDate("midnight -14 weeks", groups={"add_survey"})
     * @AppAssert\GreaterThanOrEqualDate("midnight", groups={"import_survey"})
     * @Assert\NotNull(message="common.date.not-null", groups={"add_survey", "import_survey"})
     */
    private ?DateTime $surveyPeriodStart = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @Assert\Expression("this.getSurveyPeriodEnd() >= this.getSurveyPeriodStart()", groups={"add_international_survey"}, message="common.survey.period-end.after-start")
     */
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

    public function getSurveyPeriodStartModifiedBy($modifier)
    {
        if (is_null($this->surveyPeriodStart)) return null;
        return (clone $this->surveyPeriodStart)->modify($modifier);
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
