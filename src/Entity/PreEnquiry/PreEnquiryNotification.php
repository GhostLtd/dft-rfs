<?php

namespace App\Entity\PreEnquiry;

use App\Entity\NotifyApiResponse;
use App\Entity\NotifyApiResponseTrait;
use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use App\Entity\SurveyNotificationTrait;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'pre_enquiry_notification')]
#[ORM\Index(name: 'idx_pre_enquiry_notification_lookup', columns: ['survey_id', 'type', 'sent_at'])]
#[ORM\Entity]
class PreEnquiryNotification implements ApiResponseInterface, SurveyNotificationInterface
{
    use SurveyNotificationTrait;
    use NotifyApiResponseTrait;

    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ORM\ManyToOne(targetEntity: PreEnquiry::class, inversedBy: 'notifications')]
    private ?PreEnquiry $survey = null;

    /**
     * @var Collection<int, NotifyApiResponse>
     */
    #[ORM\JoinTable(name: 'pre_enquiry_notification_notify_api_responses')]
    #[ORM\JoinColumn(name: 'notification_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'notify_api_response_id', referencedColumnName: 'id', unique: true, onDelete: 'CASCADE')]
    #[ORM\ManyToMany(targetEntity: NotifyApiResponse::class, cascade: ['persist'])]
    #[ORM\OrderBy(['timestamp' => 'DESC'])]
    protected Collection $apiResponses;

    public function __construct()
    {
        $this->apiResponses = new ArrayCollection();
    }

    public function getSurvey(): ?SurveyNotificationsInterface
    {
        return $this->survey;
    }

    public function setSurvey(SurveyNotificationsInterface $survey): static
    {
        if (!$survey instanceof PreEnquiry) {
            throw new \RuntimeException("Got a ".$survey::class.", but expected a ".PreEnquiry::class);
        }

        $this->survey = $survey;
        return $this;
    }
}
