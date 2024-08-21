<?php

namespace App\Tests\NewFunctional\Wizard\RoRo;

use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use App\Tests\DataFixtures\RoRo\SurveyFixtures;
use App\Tests\NewFunctional\Wizard\Action\FormTestAction;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SurveyWizardTest extends AbstractRoRoTest
{

    #[\Override]
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            SurveyFixtures::class
        ]);

        parent::setUp();
    }

    public function testSurveyFill(): void
    {
        $this->login('test@example.com');
        $this->clickSummaryRowLink('Southester to Waspwerp', 'View');

        $url = fn(string $path) => "#^/roro/survey/[a-f0-9\-]+{$path}\$#";
        $options = [FormTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $this->pathTestAction($url('/view'), $options);
        $this->client->clickLink('Start');

        $this->formTestAction(
            $url('/edit/introduction'),
            'introduction_continue',
            [
                new FormTestCase([], ['#introduction_isActiveForPeriod']),
                new FormTestCase([
                    'introduction[isActiveForPeriod]' => 'true',
                ], ['#introduction_dataEntryMethod']),
                new FormTestCase([
                    'introduction[isActiveForPeriod]' => 'true',
                    'introduction[dataEntryMethod]' => 'advanced',
                ], []),
            ],
            $options
        );

        // We test the bulk-entry form elsewhere, in its own test
        $this->pathTestAction($url('/edit/data-entry'), $options);
        $this->client->clickLink('Back');

        $this->formTestAction(
            $url('/edit/introduction'),
            'introduction_continue',
            [
                new FormTestCase([
                    'introduction[isActiveForPeriod]' => 'true',
                    'introduction[dataEntryMethod]' => 'manual',
                ], []),
            ],
            $options
        );

        /** @var Survey $survey */
        $survey = $this->fixtureReferenceRepository->getReference('roro:survey:1', Survey::class);
        $albania = $survey->getVehicleCounts()->findFirst(fn(string $id, VehicleCount $count) => $count->getCountryCode() === 'AL');
        $albaniaId = $albania->getId();

        $this->formTestAction(
            $url('/edit/vehicle-counts'),
            'vehicle_counts_continue',
            [
                new FormTestCase([
                    "vehicle_counts[countryVehicleCounts][{$albaniaId}][vehicleCount]" => '-1',
                ], [
                    "#vehicle_counts_countryVehicleCounts_{$albaniaId}_vehicleCount",
                ]),
                new FormTestCase([
                    "vehicle_counts[countryVehicleCounts][{$albaniaId}][vehicleCount]" => '100',
                ], []),
            ],
            $options
        );

        $this->formTestAction(
            $url('/edit/comments'),
            'comments_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $this->pathTestAction($url('/view'), $options);

        $albaniaCount = $this->client->getCrawler()
            ->filter('table > tbody > tr > .govuk-table__cell')
            ->reduce(fn(Crawler $node) => $node->text() === 'Albania')
            ->first()
            ->siblings()
            ->text();

        $this->assertEquals('100', $albaniaCount);
    }

    public function testSurveyNotActive(): void
    {
        $this->login('test@example.com');
        $this->clickSummaryRowLink('Southester to Waspwerp', 'View');

        $url = fn(string $path) => "#^/roro/survey/[a-f0-9\-]+{$path}\$#";
        $options = [FormTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $this->pathTestAction($url('/view'), $options);
        $this->client->clickLink('Start');

        $this->formTestAction(
            $url('/edit/introduction'),
            'introduction_continue',
            [
                new FormTestCase([
                    'introduction[isActiveForPeriod]' => 'false',
                ], []),
            ],
            $options
        );

        $this->formTestAction(
            $url('/edit/comments'),
            'comments_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $this->pathTestAction($url('/view'), $options);
    }
}
