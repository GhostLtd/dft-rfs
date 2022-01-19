<?php


namespace App\Utility\Domestic;


use App\Entity\Domestic\NotificationInterception;
use App\Entity\Domestic\Survey;
use App\Repository\Domestic\NotificationInterceptionRepository;
use Doctrine\ORM\PersistentCollection;

class NotificationInterceptionService
{
    private NotificationInterceptionRepository $repository;
    /** @var array | PersistentCollection */
    private $interceptions;

    public function __construct(NotificationInterceptionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function checkAndInterceptSurvey(Survey $survey)
    {
        if ($survey->getInvitationEmail()) {
            return $survey;
        }
        if (!$survey->getInvitationAddress()) {
            return $survey;
        }

        if ($interception = $this->findInterception($survey)) {
            $survey->setInvitationEmail($interception->getEmail());
        }

        return $survey;
    }

    protected function findInterception(Survey $survey): ?NotificationInterception
    {
        return $this->repository->findOneBy(['addressLine' => $survey->getInvitationAddress()->getLine1()]);
    }

    /**
     * @return array | PersistentCollection
     */
    protected function getInterceptions(): array
    {
        if (!($this->interceptions ?? false)) {
            $this->interceptions = $this->repository->findAll();
        }
        return $this->interceptions;
    }
}