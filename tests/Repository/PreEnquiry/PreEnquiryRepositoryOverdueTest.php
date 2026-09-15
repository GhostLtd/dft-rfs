<?php

namespace App\Tests\Repository\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Tests\Repository\AbstractRepositoryTestCase;
use App\Utility\Notification\NotificationType;
use App\Entity\SurveyStateInterface;
use Symfony\Component\Uid\Uuid;

class PreEnquiryRepositoryOverdueTest extends AbstractRepositoryTestCase
{
    /**
     * Built with raw SQL rather than the ORM: persisting a new PreEnquiry via the entity manager runs
     * PreEnquiryStateLifecycleSubscriber (a prePersist/postPersist Doctrine listener), which forces the state
     * to STATE_NEW and then drives the pre_enquiry workflow forward - overwriting whatever state is set here and
     * side-effecting extra notification rows. Inserting directly avoids that and lets the test set up exactly
     * the active-state-plus-notifications scenario getOverdueCount() needs to be exercised against.
     */
    public function testGetOverdueCountUsesLatestInviteNotFirst(): void
    {
        $this->loadFixtures([]);

        // (a) invited 30 days ago, never resent - overdue
        $this->createPreEnquiry('11111', [new \DateTime('-30 days')]);

        // (b) invited 30 days ago, then resent yesterday - the latest invite is recent, so not overdue
        $this->createPreEnquiry('22222', [new \DateTime('-30 days'), new \DateTime('-1 day')]);

        // (c) invited yesterday only - not overdue
        $this->createPreEnquiry('33333', [new \DateTime('-1 day')]);

        $repo = $this->getRepository(PreEnquiry::class);
        $this->assertInstanceOf(PreEnquiryRepository::class, $repo);

        $this->assertSame(1, $repo->getOverdueCount());
    }

    /**
     * @param \DateTime[] $inviteSentDates
     */
    private function createPreEnquiry(string $referenceNumber, array $inviteSentDates): void
    {
        $connection = $this->entityManager->getConnection();

        $preEnquiryId = Uuid::v4()->toRfc4122();
        $connection->insert('pre_enquiry', [
            'id' => $preEnquiryId,
            'reference_number' => $referenceNumber,
            'state' => SurveyStateInterface::STATE_INVITATION_SENT,
            'company_name' => 'Wibble Sprockets Ltd',
        ]);

        foreach ($inviteSentDates as $sentAt) {
            $connection->insert('pre_enquiry_notification', [
                'id' => Uuid::v4()->toRfc4122(),
                'survey_id' => $preEnquiryId,
                'type' => NotificationType::INVITE->value,
                'channel' => 'letter',
                'sent_at' => $sentAt->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
