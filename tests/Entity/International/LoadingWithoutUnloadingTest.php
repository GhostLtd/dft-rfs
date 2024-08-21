<?php

namespace App\Tests\Entity\International;

use App\DTO\International\LoadingWithoutUnloading;
use App\Entity\International\Action;
use App\Entity\International\Survey;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedAllFixtures;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedEightyNinePercentFixtures;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedEightyNinePercentInMultipleDropsFixtures;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedNinetyOnePercentFixtures;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedNinetyOnePercentInMultipleDropsFixtures;
use App\Tests\DataFixtures\TestSpecific\LoadingWithoutUnload\UnloadedNothing;
use App\Utility\International\LoadingWithoutUnloadingHelper;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LoadingWithoutUnloadingTest extends KernelTestCase
{
    protected AbstractDatabaseTool $databaseTool;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }

    protected function loadFixtures(array $classNames = [], bool $append = false): ReferenceRepository
    {
        $fixtures = $this->databaseTool->loadFixtures($classNames, $append);
        return $fixtures->getReferenceRepository();
    }

    public function dataLoadingWithoutUnloading(): array
    {
        return [
            [[UnloadedNothing::class], 1, 0],
            [[UnloadedAllFixtures::class], 0],
            [[UnloadedEightyNinePercentFixtures::class], 1, 20470],
            [[UnloadedEightyNinePercentInMultipleDropsFixtures::class], 1, 20470],
            [[UnloadedNinetyOnePercentFixtures::class], 0],
            [[UnloadedNinetyOnePercentInMultipleDropsFixtures::class], 0],
        ];
    }

    /**
     * @dataProvider dataLoadingWithoutUnloading
     */
    public function testLoadingWithoutUnloading(array $fixtures, int $expectedLoadingWithoutUnloadingCount, ?int $weightUnloaded=null): void
    {
        $referenceRepository = $this->loadFixtures($fixtures);

        $survey = $referenceRepository->getReference('survey:int:simple', Survey::class);

        $helper = new LoadingWithoutUnloadingHelper();

        $loadingWithoutUnloadings = iterator_to_array($helper->getLoadingWithoutUnloadingForSurvey($survey));
        $this->assertCount($expectedLoadingWithoutUnloadingCount, $loadingWithoutUnloadings);

        if ($expectedLoadingWithoutUnloadingCount === 1) {
            $trip = $referenceRepository->getReference('trip:1', Trip::class);
            $vehicle = $referenceRepository->getReference('vehicle:1', Vehicle::class);
            $loadingAction = $referenceRepository->getReference('action:loading:1', Action::class);

            $loadingWithoutUnloading = $loadingWithoutUnloadings[0];
            $this->assertInstanceOf(LoadingWithoutUnloading::class, $loadingWithoutUnloading);

            $this->assertEquals($loadingAction, $loadingWithoutUnloading->getAction());
            $this->assertEquals($trip, $loadingWithoutUnloading->getTrip());
            $this->assertEquals($vehicle, $loadingWithoutUnloading->getVehicle());
            $this->assertEquals(23000, $loadingWithoutUnloading->getWeightLoaded());
            $this->assertEquals($weightUnloaded, $loadingWithoutUnloading->getWeightUnloaded());
        }

        $expectedToHaveLoadingsWithoutUnloadings = ($expectedLoadingWithoutUnloadingCount > 0);
        $this->assertEquals($expectedToHaveLoadingsWithoutUnloadings, $helper->hasLoadingWithoutUnloading($survey));
    }
}
