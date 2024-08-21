<?php

namespace App\EventListener\InternationalSurvey;

use App\Entity\International\NotificationInterception;
use App\Entity\International\NotificationInterceptionCompanyName;
use App\Entity\International\Survey;
use App\EventListener\AbstractLcniChangeListener;
use App\Repository\International\SurveyRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::onFlush)]
class LcniChangeListener extends AbstractLcniChangeListener
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
                $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails($entity->getAllNames(), null);

                $emails = $entity->getEmails();
                $this->updateSurveysEmails(
                    $surveys,
                    $emails,
                    'lcni.add',
                    ['emails' => $emails],
                    fn(Survey $survey) => "LCNI add: Adding invitation emails to survey {$survey->getId()}"
                );
            }

            if ($entity instanceof NotificationInterceptionCompanyName) {
                $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails([$entity->getName()], null);

                $emails = $entity->getNotificationInterception()->getEmails();
                $this->updateSurveysEmails(
                    $surveys,
                    $emails,
                    'lcni.additional-name.add',
                    ['emails' => $emails],
                    fn(Survey $survey) => "LCNI add additional name: Adding invitation emails to survey {$survey->getId()}"
                );
            }
        }

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof NotificationInterception) {
                $changeSet = $this->uow->getEntityChangeSet($entity);

                if (isset($changeSet['emails']) && is_array($changeSet['emails'])) {
                    [$oldEmails, $newEmails] = $changeSet['emails'];
                    $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails([$entity->getPrimaryName()], $oldEmails);

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

                if (isset($changeSet['primaryName']) && is_array($changeSet['primaryName'])) {
                    // Remove emails from any surveys matching the old addressLine
                    [$oldPrimaryName, $newPrimaryName] = $changeSet['primaryName'];

                    $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails([$oldPrimaryName], $entity->getEmails());

                    $this->updateSurveysEmails(
                        $surveys,
                        null,
                        'lcni.update.address.remove',
                        ['emails' => $entity->getEmails()],
                        fn(Survey $survey) => "LCNI primary name change: Removing invitation emails from survey {$survey->getId()}"
                    );

                    // Add emails to any surveys matching the new addressLine with no emails
                    $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails([$newPrimaryName], null);

                    $this->updateSurveysEmails(
                        $surveys,
                        $entity->getEmails(),
                        'lcni.update.address.add',
                        ['emails' => $entity->getEmails()],
                        fn(Survey $survey) => "LCNI primary name change: Adding invitation emails to survey {$survey->getId()}"
                    );
                }
            }
        }

        foreach ($this->uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof NotificationInterception) {
                $emails = $entity->getEmails();
                $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails($entity->getAllNames(), $emails);

                $this->updateSurveysEmails(
                    $surveys,
                    null,
                    'lcni.delete',
                    ['emails' => $entity->getEmails()],
                    fn(Survey $survey) => "LCNI deletion: Removing invitation emails from survey {$survey->getId()}"
                );
            }

            if ($entity instanceof NotificationInterceptionCompanyName) {
                try {
                    // So we have a NotificationInterceptionCompany, and we need to find the NotificationInterception
                    // it was associated with so that we can get the emails value.

                    // Unfortunately ->getNotificationInterception() will return null at this point, due to the
                    // removal of this companyName from the interception, so we have to find it for ourselves.
                    $notificationId = $this->entityManager->getConnection()
                        ->createQueryBuilder()
                        ->select('c.notification_interception_id')
                        ->from('international_notification_interception_company_name', 'c')
                        ->where('c.id = :id')
                        ->setParameter('id', $entity->getId())
                        ->executeQuery()
                        ->fetchOne();

                    $notificationIntercept = $this->entityManager->find(NotificationInterception::class, $notificationId);
                } catch (Exception) {
                    continue;
                }

                $emails = $notificationIntercept->getEmails();
                $surveys = $this->surveyRepository->findSurveysMatchingLcniNamesAndEmails([$entity->getName()], $emails);

                $this->updateSurveysEmails(
                    $surveys,
                    null,
                    'lcni.additional-name.delete',
                    ['emails' => $emails],
                    fn(Survey $survey) => "LCNI additional name deletion: Removing invitation emails from survey {$survey->getId()}"
                );
            }
        }
    }
}
