<?php

namespace App\Tests\EventSubscriber\International;

use App\Entity\AuditLog\AuditLog;
use App\Entity\International\NotificationInterception;
use App\Entity\International\NotificationInterceptionCompanyName;
use App\Entity\International\Survey;
use App\Entity\PasscodeUser;
use App\Repository\AuditLog\AuditLogRepository;
use App\Repository\International\SurveyRepository;
use App\Tests\DataFixtures\TestSpecific\InternationalLcniChangeSubscriberFixtures;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LcniChangeSubscriberTest extends KernelTestCase
{
    protected AuditLogRepository $auditLogRepository;
    protected EntityManagerInterface $entityManager;
    protected ReferenceRepository $referenceRepository;
    protected SurveyRepository $surveyRepository;

    #[\Override]
    public function setUp(): void
    {
        $container = static::getContainer();

        $securityMock = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser'])
            ->getMock();

        $user = (new PasscodeUser())
            ->setUsername('wibble@example.com');

        $securityMock->method('getUser')
            ->willReturn($user);

        $container->set('app.test.security', $securityMock);

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->auditLogRepository = $this->entityManager->getRepository(AuditLog::class);
        $this->surveyRepository = $this->entityManager->getRepository(Survey::class);

        $databaseTool = $container->get(DatabaseToolCollection::class)->get();

        $fixtures = $databaseTool->loadFixtures([
            InternationalLcniChangeSubscriberFixtures::class
        ]);

        $this->referenceRepository = $fixtures->getReferenceRepository();

        try {
            $this->entityManager->getConnection()
                ->createQueryBuilder()
                ->delete('audit_log')
                ->executeStatement();
        } catch (Exception) {}
    }

    public function testAddLcni(): void
    {
        $emails = 'lcni@example.com';
        $name = 'Blue Rover';

        $lcni = (new NotificationInterception())
            ->setPrimaryName($name)
            ->setEmails($emails);

        $this->entityManager->persist($lcni);
        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails = :emails')
            ->setParameter('emails', $emails)
            ->getQuery()
            ->execute();

        // Expect emails to only be set on five surveys which had emails NULL, state non-closed and matching company name
        $this->assertCount(5, $surveys);

        // ***** See the fixtures for an explanation of the strange codes in the referenceNumber fields *****
        foreach($surveys as $survey) {
            $this->assertEquals('NBA', $survey->getReferenceNumber()); // email: null, address: blue, state: active
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(5, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $this->assertEquals('lcni.add', $auditLog->getCategory());
            $this->assertEquals('wibble@example.com', $auditLog->getUsername());
            $this->assertEquals($emails, $auditLog->getData()['emails'] ?? null);
        }
    }

    public function testDeleteLcni(): void
    {
        /** @var NotificationInterception $lcni */
        $lcni = $this->referenceRepository->getReference('intercept:1',NotificationInterception::class);

        $this->entityManager->remove($lcni);
        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->join('s.company', 'c')
            ->where('s.invitationEmails IS NULL')
            ->andWhere('c.businessName = :company')
            ->setParameter('company', 'Red Rover')
            ->getQuery()
            ->execute();

        // Expect to find 13 surveys:
        // - 8 which already had emails null, and company Red Rover
        // - 5 which previously had state non-closed, company Red Rover and emails red@example.com (i.e. matching the existing LCNI)
        $this->assertCount(13, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getReferenceNumber(),
                $this->logicalOr('RRA', 'NRA', 'NRC')
            );
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(5, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $this->assertEquals('lcni.delete', $auditLog->getCategory());
            $this->assertEquals('wibble@example.com', $auditLog->getUsername());
            $this->assertEquals('red@example.com', $auditLog->getData()['emails'] ?? null);
        }
    }

    public function testChangeLcniAddress(): void
    {
        /** @var NotificationInterception $lcni */
        $lcni = $this->referenceRepository->getReference('intercept:1', NotificationInterception::class);
        $lcni->setPrimaryName('Blue Rover');

        $this->entityManager->flush();

        // -----
        // Firstly, any active survey with matching old company, and emails, should have had its emails NULLed:
        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->join('s.company', 'c')
            ->where('s.invitationEmails IS NULL')
            ->andWhere('c.businessName = :company')
            ->setParameter('company', 'Red Rover')
            ->getQuery()
            ->execute();

        // Expect emails to be null on 13 surveys:
        // - 9 which already had emails null, and company Red Rover
        // - 5 which had state non-closed, emails red@example.com, and company Red Rover
        $this->assertCount(13, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getReferenceNumber(),
                $this->logicalOr('RRA', 'NRA', 'NRC')
            );
        }

        // -----
        // Secondly, any active survey with matching new address and NULL emails should have had its emails set
        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->join('s.company', 'c')
            ->where('c.businessName = :company')
            ->andWhere('s.invitationEmails = :emails')
            ->setParameter('company', 'Blue Rover')
            ->setParameter('emails', 'red@example.com')
            ->getQuery()
            ->execute();

        // Expect emails to be null on 13 surveys:
        // - 8 which already had emails red@example.com, and company Blue Rover
        // - 5 which previously had state non-closed, emails NULL, and company Blue Rover
        $this->assertCount(13, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getReferenceNumber(),
                $this->logicalOr('RBA', 'RBC', 'NBA')
            );
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(10, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $category = $auditLog->getCategory();

            if ($category === 'lcni.update.address.remove') {
                $this->assertEquals('wibble@example.com', $auditLog->getUsername());
                $this->assertEquals('red@example.com', $auditLog->getData()['emails'] ?? null);
            } else if ($category === 'lcni.update.address.add') {
                $this->assertEquals('wibble@example.com', $auditLog->getUsername());
                $this->assertEquals('red@example.com', $auditLog->getData()['emails'] ?? null);
            } else {
                $this->fail("Unexpected auditLog category: {$category}");
            }
        }
    }

    public function testChangeLcniEmails(): void
    {
        /** @var NotificationInterception $lcni */
        $lcni = $this->referenceRepository->getReference('intercept:1', NotificationInterception::class);
        $lcni->setEmails('lcni@example.com');

        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails = :emails')
            ->setParameter('emails', 'lcni@example.com')
            ->getQuery()
            ->execute();

        // Expect to find 5 surveys which:
        // - previously had state non-closed, emails red@example.com, and company Red Rover
        //   (i.e. matching LCNI old email address)
        $this->assertCount(5, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertEquals('RRA', $survey->getReferenceNumber());
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(5, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $this->assertEquals('lcni.update.emails', $auditLog->getCategory());
            $this->assertEquals('wibble@example.com', $auditLog->getUsername());
            $this->assertEquals('red@example.com', $auditLog->getData()['old_emails'] ?? null);
            $this->assertEquals('lcni@example.com', $auditLog->getData()['new_emails'] ?? null);
        }
    }

    public function testAddAdditionalCompanyName(): void
    {
        $additionalName = (new NotificationInterceptionCompanyName())
            ->setName('Orange Rover');

        /** @var NotificationInterception $lcni */
        $lcni = $this->referenceRepository->getReference('intercept:1', NotificationInterception::class);
        $lcni->addAdditionalName($additionalName);

        $this->entityManager->persist($additionalName);
        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->select('s, c')
            ->join('s.company', 'c')
            ->where('s.invitationEmails = :emails')
            ->andWhere('c.businessName = :name')
            ->setParameter('emails', 'red@example.com')
            ->setParameter('name', 'Orange Rover')
            ->getQuery()
            ->execute();

        // Expect 13 surveys:
        // - 8 which already had emails red@example.com, and company Orange Rover
        // - 5 which previously had state non-closed, emails NULL, and company Orange Rover
        $this->assertCount(13, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getReferenceNumber(),
                $this->logicalOr('ROA', 'ROC', 'NOA')
            );
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(5, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $this->assertEquals('lcni.additional-name.add', $auditLog->getCategory());
            $this->assertEquals('wibble@example.com', $auditLog->getUsername());
            $this->assertEquals('red@example.com', $auditLog->getData()['emails'] ?? null);
        }
    }

    public function testRemoveAdditionalCompanyName(): void
    {
        /** @var NotificationInterception $lcni */
        $lcni = $this->referenceRepository->getReference('intercept:2', NotificationInterception::class);

        foreach($lcni->getAdditionalNames() as $additionalName) {
            $lcni->removeAdditionalName($additionalName);
        }

        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->select('s, c')
            ->join('s.company', 'c')
            ->where('s.invitationEmails = :emails')
            ->andWhere('c.businessName = :name')
            ->setParameter('emails', 'blue@example.com')
            ->setParameter('name', 'Purple Rover')
            ->getQuery()
            ->execute();

        // Expect 3 surveys:
        // - We originally had 8 blue@example.com / Purple Rover
        // - When the LCNI additional name was removed, the active ones should have had their emails set to null, leaving 3
        $this->assertCount(3, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertEquals('BPC', $survey->getReferenceNumber());
        }

        $auditLogs = $this->auditLogRepository->findAll();
        $this->assertCount(5, $auditLogs);

        foreach($auditLogs as $auditLog) {
            $this->assertEquals('lcni.additional-name.delete', $auditLog->getCategory());
            $this->assertEquals('wibble@example.com', $auditLog->getUsername());
            $this->assertEquals('blue@example.com', $auditLog->getData()['emails'] ?? null);
        }
    }
}
