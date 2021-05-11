<?php


namespace App\Entity\International;


use App\Entity\NoteTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("international_survey_note")
 */
class SurveyNote
{
    use NoteTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Survey::class, inversedBy="notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?Survey $survey;

    /**
     * @param Survey|null $survey
     * @return SurveyNote
     */
    public function setSurvey(?Survey $survey): SurveyNote
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return Survey|null
     */
    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

}