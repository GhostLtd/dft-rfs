<?php

namespace App\Tests\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\Embeddable\LongAddress;
use App\Entity\International\Company;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\NotificationInterception;
use App\Repository\NotificationInterceptionRepository;
use App\Utility\LcniDisplayService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

class LcniDisplayServiceTest extends TestCase
{
    private function createMockRepository(?NotificationInterception $returnValue = null): NotificationInterceptionRepository
    {
        $repository = $this->createMock(NotificationInterceptionRepository::class);
        $repository->method('findOneByBusinessName')->willReturn($returnValue);
        return $repository;
    }

    private function createMockLockFactory(bool $lockIsHeld = false): LockFactory
    {
        $lock = $this->createMock(LockInterface::class);
        $lock->method('acquire')->willReturn(!$lockIsHeld);
        $lock->method('release')->willReturn(null);

        $lockFactory = $this->createMock(LockFactory::class);
        $lockFactory->method('createLock')->willReturn($lock);
        return $lockFactory;
    }

    private function createDomesticSurvey(string $companyName): DomesticSurvey
    {
        $address = new LongAddress();
        $address->setLine1($companyName);

        $survey = $this->createMock(DomesticSurvey::class);
        $survey->method('getInvitationAddress')->willReturn($address);

        return $survey;
    }

    private function createInternationalSurvey(string $companyName): InternationalSurvey
    {
        $company = $this->createMock(Company::class);
        $company->method('getBusinessName')->willReturn($companyName);

        $survey = $this->createMock(InternationalSurvey::class);
        $survey->method('getCompany')->willReturn($company);

        return $survey;
    }

    private function createNotificationInterception(?string $csrgtEmails, ?string $irhsEmails): NotificationInterception
    {
        $interception = new NotificationInterception();
        $interception->setCsrgtEmails($csrgtEmails);
        $interception->setIrhsEmails($irhsEmails);
        return $interception;
    }

    public function testGetDisplayInfoForSurveyWithNoMatch(): void
    {
        $repository = $this->createMockRepository(null);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createDomesticSurvey('Acme Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNull($result);
    }

    public function testGetDisplayInfoForDomesticSurveyWithMatch(): void
    {
        $interception = $this->createNotificationInterception('lcni@example.com', null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createDomesticSurvey('Acme Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNotNull($result);
        $this->assertEquals(['lcni@example.com'], $result['emails']);
    }

    public function testGetDisplayInfoForInternationalSurveyWithMatch(): void
    {
        $interception = $this->createNotificationInterception(null, 'irhs@example.com');
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createInternationalSurvey('International Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNotNull($result);
        $this->assertEquals(['irhs@example.com'], $result['emails']);
    }

    public function testGetDisplayInfoForSurveyWithEmptyEmails(): void
    {
        $interception = $this->createNotificationInterception(null, null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createDomesticSurvey('Acme Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNull($result);
    }

    public function testGetDisplayInfoForCompanyName(): void
    {
        $interception = $this->createNotificationInterception('lcni@example.com', null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $result = $service->getDisplayInfoForCompanyName('Acme Corp', 'csrgt');

        $this->assertNotNull($result);
        $this->assertEquals(['lcni@example.com'], $result['emails']);
    }

    public function testGetDisplayInfoForCompanyNameNoMatch(): void
    {
        $repository = $this->createMockRepository(null);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $result = $service->getDisplayInfoForCompanyName('Unknown Corp', 'csrgt');

        $this->assertNull($result);
    }

    public function testGetDisplayInfoWithMultipleEmails(): void
    {
        $interception = $this->createNotificationInterception('email1@example.com, email2@example.com', null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createDomesticSurvey('Acme Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNotNull($result);
        $this->assertEquals(['email1@example.com', 'email2@example.com'], $result['emails']);
    }

    public function testRepositoryIsCalledWithCorrectBusinessNameForDomestic(): void
    {
        $repository = $this->createMock(NotificationInterceptionRepository::class);
        $repository->expects($this->once())
            ->method('findOneByBusinessName')
            ->with('Test Company Name')
            ->willReturn(null);

        $lockFactory = $this->createMockLockFactory();
        $service = new LcniDisplayService($repository, $lockFactory);
        $survey = $this->createDomesticSurvey('Test Company Name');

        $service->getDisplayInfoForSurvey($survey);
    }

    public function testReturnsLockedWhenDvlaImportInProgress(): void
    {
        $interception = $this->createNotificationInterception('lcni@example.com', null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory(lockIsHeld: true);
        $service = new LcniDisplayService($repository, $lockFactory);

        $survey = $this->createDomesticSurvey('Acme Corp');

        $result = $service->getDisplayInfoForSurvey($survey);

        $this->assertNotNull($result);
        $this->assertTrue($result['locked']);
        $this->assertArrayNotHasKey('emails', $result);
    }

    public function testReturnsLockedForCompanyNameWhenDvlaImportInProgress(): void
    {
        $interception = $this->createNotificationInterception('lcni@example.com', null);
        $repository = $this->createMockRepository($interception);
        $lockFactory = $this->createMockLockFactory(lockIsHeld: true);
        $service = new LcniDisplayService($repository, $lockFactory);

        $result = $service->getDisplayInfoForCompanyName('Acme Corp', 'csrgt');

        $this->assertNotNull($result);
        $this->assertTrue($result['locked']);
        $this->assertArrayNotHasKey('emails', $result);
    }
}
