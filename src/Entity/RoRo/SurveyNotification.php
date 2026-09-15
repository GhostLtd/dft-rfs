<?php

namespace App\Entity\RoRo;

use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Entity\SurveyNotificationTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'roro_survey_notification')]
#[ORM\Index(name: 'idx_roro_survey_notification_lookup', columns: ['survey_id', 'type', 'sent_at'])]
#[ORM\Entity]
class SurveyNotification implements SurveyNotificationInterface
{
    use SurveyNotificationTrait;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'notifications')]
    private ?Survey $survey = null;

    public function getSurvey(): ?SurveyNotificationsInterface
    {
        return $this->survey;
    }

    public function setSurvey(SurveyNotificationsInterface $survey): static
    {
        if (!$survey instanceof Survey) {
            throw new \RuntimeException("Got a ".$survey::class.", but expected a ".Survey::class);
        }

        $this->survey = $survey;
        return $this;
    }
}
