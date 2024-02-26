<?php

namespace App\Tests\EventSubscriber\Domestic;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\NotificationInterception;
use App\Entity\Domestic\Survey;
use App\Entity\PasscodeUser;
use App\Repository\AuditLog\AuditLogRepository;
use App\Repository\Domestic\SurveyRepository;
use App\Tests\DataFixtures\TestSpecific\DomesticLcniChangeSubscriberFixtures;
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
            DomesticLcniChangeSubscriberFixtures::class
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
        $addressLine = '234 Blue Road';

        $lcni = (new NotificationInterception())
            ->setAddressLine($addressLine)
            ->setEmails($emails);

        $this->entityManager->persist($lcni);
        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails = :emails')
            ->setParameter('emails', $emails)
            ->getQuery()
            ->execute();

        // Expect emails to only be set on five surveys which had emails NULL, state non-closed and matching address
        $this->assertCount(5, $surveys);

        // ***** See the fixtures for an explanation of the strange codes in the RegistrationMark fields *****
        foreach($surveys as $survey) {
            $this->assertEquals('NBA', $survey->getRegistrationMark()); // email: null, address: blue, state: active
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
        $lcni = $this->referenceRepository->getReference('intercept', NotificationInterception::class);

        $this->entityManager->remove($lcni);
        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails IS NULL')
            ->andWhere('s.invitationAddress.line1 = :addressLine')
            ->setParameter('addressLine', '123 Red Road')
            ->getQuery()
            ->execute();

        // Expect to find 14 surveys:
        // - 9 which already had emails null, and address 123 Red Road
        // - 5 which previously had state non-closed, address 123 Red Road and emails red@example.com (i.e. matching the existing LCNI)
        $this->assertCount(14, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getRegistrationMark(),
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
        $lcni = $this->referenceRepository->getReference('intercept', NotificationInterception::class);
        $lcni->setAddressLine('234 Blue Road');

        $this->entityManager->flush();

        // -----
        // Firstly, any active survey with matching old address, and emails, should have had its emails NULLed:
        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails IS NULL')
            ->andWhere('s.invitationAddress.line1 = :addressLine')
            ->setParameter('addressLine', '123 Red Road')
            ->getQuery()
            ->execute();

        // Expect emails to be null on 14 surveys:
        // - 9 which already had emails null, and address 123 Red Road
        // - 5 which had state non-closed, emails red@example.com, and address 123 Red Road
        $this->assertCount(14, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getRegistrationMark(),
                $this->logicalOr('RRA', 'NRA', 'NRC')
            );
        }

        // -----
        // Secondly, any active survey with matching new address and NULL emails should have had its emails set
        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationAddress.line1 = :addressLine')
            ->andWhere('s.invitationEmails = :emails')
            ->setParameter('addressLine', '234 Blue Road')
            ->setParameter('emails', 'red@example.com')
            ->getQuery()
            ->execute();

        // Expect emails to be null on 14 surveys:
        // - 9 which already had emails red@example.com, and address 234 Blue Road
        // - 5 which previously had state non-closed, emails NULL, and address 234 Blue Road
        $this->assertCount(14, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertThat($survey->getRegistrationMark(),
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
        $lcni = $this->referenceRepository->getReference('intercept', NotificationInterception::class);
        $lcni->setEmails('lcni@example.com');

        $this->entityManager->flush();

        /** @var Survey[] $surveys */
        $surveys = $this->surveyRepository->createQueryBuilder('s')
            ->where('s.invitationEmails = :emails')
            ->setParameter('emails', 'lcni@example.com')
            ->getQuery()
            ->execute();

        // Expect to find 5 surveys which:
        // - previously had state non-closed, emails red@example.com, and address 123 Red Road
        //   (i.e. matching LCNI old email address)
        $this->assertCount(5, $surveys);

        // Check that the surveys are of the expected types
        foreach($surveys as $survey) {
            $this->assertEquals('RRA', $survey->getRegistrationMark());
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
}
