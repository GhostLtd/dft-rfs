<?php

namespace App\Entity;

use App\Entity\LongAddress; // PHPstorm indicates this isn't needed, but it is
use App\Form\Validator as AppAssert;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

trait HaulageSurveyTrait {
    use SurveyTrait;

    /**
     * @ORM\Column(type="date", nullable=true)
     * add_survey group is used by both international and domestic
     * @Assert\NotNull(message="common.date.not-null", groups={"add_survey"})
     */
    private $surveyPeriodStart;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $surveyPeriodEnd;

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
}
