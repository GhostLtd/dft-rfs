<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\LcniLookupController;
use App\Utility\EmailBlacklistService;
use App\Utility\LcniDisplayService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class LcniLookupControllerTest extends TestCase
{
    private function createMockService(?array $returnValue): LcniDisplayService
    {
        $service = $this->createMock(LcniDisplayService::class);
        $service->method('getDisplayInfoForCompanyName')->willReturn($returnValue);
        return $service;
    }

    private function createMockBlacklistService(array $blacklisted = []): EmailBlacklistService
    {
        $service = $this->createMock(EmailBlacklistService::class);
        $service->method('getBlacklistedFromList')->willReturn($blacklisted);
        return $service;
    }

    private function createController(LcniDisplayService $lcniService, ?EmailBlacklistService $blacklistService = null): LcniLookupController
    {
        return new LcniLookupController($lcniService, $blacklistService ?? $this->createMockBlacklistService());
    }

    public function testLookupWithNoMatch(): void
    {
        $service = $this->createMockService(null);
        $controller = $this->createController($service);

        $request = new Request(['companyName' => 'Unknown Company', 'surveyType' => 'csrgt']);
        $response = $controller->lookup($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['found']);
    }

    public function testLookupWithMatch(): void
    {
        $service = $this->createMockService([
            'emails' => ['lcni@example.com'],
        ]);
        $controller = $this->createController($service);

        $request = new Request(['companyName' => 'Acme Corp', 'surveyType' => 'csrgt']);
        $response = $controller->lookup($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['found']);
        $this->assertEquals(['lcni@example.com'], $data['emails']);
        $this->assertEquals([], $data['blacklistedEmails']);
    }

    public function testLookupRequiresCompanyName(): void
    {
        $service = $this->createMockService(null);
        $controller = $this->createController($service);

        $request = new Request(['surveyType' => 'csrgt']);
        $response = $controller->lookup($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('companyName', $data['error']);
    }

    public function testLookupRequiresSurveyType(): void
    {
        $service = $this->createMockService(null);
        $controller = $this->createController($service);

        $request = new Request(['companyName' => 'Acme Corp']);
        $response = $controller->lookup($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('surveyType', $data['error']);
    }

    public function testLookupRequiresValidSurveyType(): void
    {
        $service = $this->createMockService(null);
        $controller = $this->createController($service);

        $request = new Request(['companyName' => 'Acme Corp', 'surveyType' => 'invalid']);
        $response = $controller->lookup($request);

        $this->assertEquals(400, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('csrgt or irhs', $data['error']);
    }

    public function testServiceIsCalledWithCorrectParameters(): void
    {
        $service = $this->createMock(LcniDisplayService::class);
        $service->expects($this->once())
            ->method('getDisplayInfoForCompanyName')
            ->with('Test Company', 'irhs')
            ->willReturn(null);

        $controller = $this->createController($service);
        $request = new Request(['companyName' => 'Test Company', 'surveyType' => 'irhs']);
        $controller->lookup($request);
    }

    public function testLookupReturnsLockedWhenServiceReturnsLocked(): void
    {
        $service = $this->createMockService(['locked' => true]);
        $controller = $this->createController($service);

        $request = new Request(['companyName' => 'Acme Corp', 'surveyType' => 'csrgt']);
        $response = $controller->lookup($request);

        $this->assertEquals(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['locked']);
        $this->assertArrayNotHasKey('found', $data);
        $this->assertArrayNotHasKey('emails', $data);
    }
}
