<?php

namespace App\EventSubscriber\DomesticSurvey;

use App\Entity\Domestic\NotificationInterception;
use App\Entity\Domestic\Survey;
use App\EventSubscriber\AbstractLcniChangeSubscriber;
use App\Repository\Domestic\SurveyRepository;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

class LcniChangeSubscriber extends AbstractLcniChangeSubscriber
{
    public function __construct(
        LoggerInterface            $logger,
        Security                   $security,
        protected SurveyRepository $surveyRepository,
    )
    {
        parent::__construct($logger, $security);
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->initialise(Survey::class, $args->getObjectManager())) {
            // This means this is a flush on the AuditLog entityManager rather than the default entityManager
            return;
        }

        foreach ($this->uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof NotificationInterception) {
                $surveys = $this->surveyRepository->findSurveysMatchingLcniAddressWithNoEmails($entity);

                $this->updateSurveysEmails(
                    $surveys,
                    $entity->getEmails(),
                    'lcni.add',
                    ['emails' => $entity->getEmails()],
                    fn(Survey $survey) => "LCNI add: Adding invitation emails to survey {$survey->getId()}"
                );
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof NotificationInterception) {
                $changeSet = $this->uow->getEntityChangeSet($entity);

                if (isset($changeSet['emails'])) {
                    [$oldEmails, $newEmails] = $changeSet['emails'];

                    $oldLcni = (new NotificationInterception())
                        ->setEmails($oldEmails)
                        ->setAddressLine($entity->getAddressLine());

                    $surveys = $this->surveyRepository->findSurveysMatchingLcniAddressAndEmails($oldLcni);

                    $this->updateSurveysEmails(
                        $surveys,
                        $newEmails,
                        'lcni.update.emails',
                        [
                            'old_emails' => $oldEmails,
                            'new_emails' => $newEmails,
                        ],
                        fn(Survey $survey) => "LCNI email change: Updating invitation emails on survey {$survey->getId()}"
                    );
                }

                if (isset($changeSet['addressLine'])) {
                    [$oldAddressLine, $newAddressLine] = $changeSet['addressLine'];

                    // Remove emails from any surveys matching the old addressLine/emails
                    $oldLcni = (new NotificationInterception())
                        ->setEmails($entity->getEmails())
                        ->setAddressLine($oldAddressLine);

                    $surveys = $this->surveyRepository->findSurveysMatchingLcniAddressAndEmails($oldLcni);

                    $this->updateSurveysEmails(
                        $surveys,
                        null,
                        'lcni.update.address.remove',
                        ['emails' => $entity->getEmails()],
                        fn(Survey $survey) => "LCNI address change: Removing invitation emails from survey {$survey->getId()}"
                    );

                    // Add emails to any surveys matching the new addressLine with no emails
                    $oldLcni = (new NotificationInterception())
                        ->setEmails($entity->getEmails())
                        ->setAddressLine($newAddressLine);

                    $surveys = $this->surveyRepository->findSurveysMatchingLcniAddressWithNoEmails($oldLcni);

                    $this->updateSurveysEmails(
                        $surveys,
                        $entity->getEmails(),
                        'lcni.update.address.add',
                        ['emails' => $entity->getEmails()],
                        fn(Survey $survey) => "LCNI address change: Adding invitation emails to survey {$survey->getId()}"
                    );
                }
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof NotificationInterception) {
                $surveys = $this->surveyRepository->findSurveysMatchingLcniAddressAndEmails($entity);

                $this->updateSurveysEmails(
                    $surveys,
                    null,
                    'lcni.delete',
                    ['emails' => $entity->getEmails()],
                    fn(Survey $survey) => "LCNI deletion: Removing invitation emails from survey {$survey->getId()}"
                );
            }
        }
    }
}
