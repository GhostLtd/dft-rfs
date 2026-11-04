<?php

namespace App\Tests\Functional\Admin\International;

use App\Entity\International\Trip;
use App\Features;
use App\Tests\DataFixtures\International\TripFixtures;
use App\Tests\Functional\Admin\AbstractAdminFunctionalTestCase;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Admin trip edit: origin/destination become postcode-only for IRHS surveys in postcode-only mode, and stay free text
 * for surveys before the changeover date. (TripFixtures' survey period starts 2021-05-01.)
 */
class TripPostcodeTest extends AbstractAdminFunctionalTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($_ENV['APP_IRHS_POSTCODE_CHANGEOVER_DATE']);
        $this->setFeatures();
    }

    private function setFeatures(string ...$features): void
    {
        $_ENV['APP_FEATURES'] = implode(',', $features);
        (new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, []);
    }

    private function getTripId(): string
    {
        return $this->getEntityManager()->getRepository(Trip::class)->findOneBy([])->getId();
    }

    private function submitOriginAndDestination(KernelBrowser $browser, string $tripId, string $origin, string $destination): void
    {
        $crawler = $browser->request('GET', "/irhs/trip/{$tripId}/edit");
        self::assertEquals(200, $browser->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Save changes')->form();
        $form['trip[origin]'] = $origin;
        $form['trip[destination]'] = $destination;
        $browser->submit($form);
    }

    private function assertFieldErrors(KernelBrowser $browser, array $expectedFieldIds): void
    {
        $crawler = $browser->getCrawler();
        foreach (['trip_origin', 'trip_destination'] as $fieldId) {
            $hasError = $crawler->filter("#{$fieldId}-error")->count() > 0;
            self::assertSame(in_array($fieldId, $expectedFieldIds), $hasError, "Unexpected error state for #{$fieldId}");
        }
    }

    private function assertStoredTrip(string $tripId, string $origin, string $destination): void
    {
        $em = $this->getEntityManager();
        $em->clear();
        $trip = $em->getRepository(Trip::class)->find($tripId);

        self::assertSame($origin, $trip->getOrigin());
        self::assertSame($destination, $trip->getDestination());
    }

    public function testPostcodeOnlySurvey(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER, Features::OUTWARD_CODE_CHECK);
        $browser = $this->getBrowserLoadFixturesAndLogin([TripFixtures::class]);
        $tripId = $this->getTripId();

        $crawler = $browser->request('GET', "/irhs/trip/{$tripId}/edit");
        self::assertStringContainsString('Initial origin postcode', $crawler->filter('label[for=trip_origin]')->text());
        self::assertStringContainsString('Final destination postcode', $crawler->filter('label[for=trip_destination]')->text());

        $this->submitOriginAndDestination($browser, $tripId, 'Felpham', 'ZZ1 1AA');
        $this->assertFieldErrors($browser, ['trip_origin', 'trip_destination']);
        $this->assertStoredTrip($tripId, 'Chichester', 'Chichester');

        $this->submitOriginAndDestination($browser, $tripId, 'PO22 7AA', 'BT7 1NN');
        $this->assertFieldErrors($browser, []);
        $this->assertStoredTrip($tripId, 'PO22 7AA', 'BT7 1NN');
    }

    public function testEditAndDeleteHeadingsFormatPostcodes(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin([TripFixtures::class]);
        $em = $this->getEntityManager();
        $trip = $em->getRepository(Trip::class)->findOneBy([]);
        $trip->setOrigin('po227aa')->setDestination('bt7 1nn');
        $em->flush();

        foreach (['edit', 'delete'] as $page) {
            $crawler = $browser->request('GET', "/irhs/trip/{$trip->getId()}/{$page}");
            self::assertEquals(200, $browser->getResponse()->getStatusCode());

            $heading = $crawler->filter('h1')->text();
            self::assertStringContainsString('PO22 7AA', $heading, "{$page} heading");
            self::assertStringContainsString('BT7 1NN', $heading, "{$page} heading");
        }
    }

    public function testSurveyBeforeChangeoverDateKeepsFreeText(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER, Features::OUTWARD_CODE_CHECK);
        $_ENV['APP_IRHS_POSTCODE_CHANGEOVER_DATE'] = '2030-01-01';
        $browser = $this->getBrowserLoadFixturesAndLogin([TripFixtures::class]);
        $tripId = $this->getTripId();

        $crawler = $browser->request('GET', "/irhs/trip/{$tripId}/edit");
        self::assertStringContainsString('Initial origin', $crawler->filter('label[for=trip_origin]')->text());
        self::assertStringNotContainsString('postcode', $crawler->filter('label[for=trip_origin]')->text());

        $this->submitOriginAndDestination($browser, $tripId, 'Felpham', 'Gosport');
        $this->assertFieldErrors($browser, []);
        $this->assertStoredTrip($tripId, 'Felpham', 'Gosport');
    }
}
