<?php

namespace App\Entity\RoRo;

use App\Entity\NoteInterface;
use App\Entity\NoteTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table('roro_survey_note')]
#[ORM\Entity]
class SurveyNote implements NoteInterface
{
    use NoteTrait;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'notes')]
    private ?Survey $survey = null;

    public function setSurvey(?Survey $survey): SurveyNote
    {
        $this->survey = $survey;
        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }
}
