<?php

namespace App\Entity\RoRo;

use App\Entity\NoteInterface;
use App\Entity\NoteTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("roro_survey_note")
 */
class SurveyNote implements NoteInterface
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