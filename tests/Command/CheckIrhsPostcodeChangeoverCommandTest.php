<?php

namespace App\Tests\Command;

use App\Entity\International\Trip;
use App\Entity\SurveyStateInterface;
use App\Features;
use App\Tests\DataFixtures\International\TripFixtures;
use App\Utility\PostcodeChangeoverHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Uses the real validator (via the container), so a constraint that silently never runs — e.g. one built with
 * groups: [] — shows up here as missed failures.
 *
 * TripFixtures: one IRHS survey (period start 2021-05-01, state "new") with one trip, origin/destination "Chichester".
 */
class CheckIrhsPostcodeChangeoverCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->setFeatures(Features::OUTWARD_CODE_CHECK);

        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([TripFixtures::class]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setFeatures();
        parent::tearDown();
    }

    private function setFeatures(string ...$features): void
    {
        $_ENV['APP_FEATURES'] = implode(',', $features);
        (new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, []);
    }

    private function updateTrip(?string $origin = null, ?string $destination = null, ?string $state = null): void
    {
        $trip = $this->entityManager->getRepository(Trip::class)->findOneBy([]);
        if ($origin !== null) {
            $trip->setOrigin($origin);
        }
        if ($destination !== null) {
            $trip->setDestination($destination);
        }
        if ($state !== null) {
            $trip->getVehicle()->getSurveyResponse()->getSurvey()->setState($state);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function check(string $date = '2021-05-01'): CommandTester
    {
        $tester = new CommandTester((new Application(static::$kernel))->find('app:irhs:check-postcode-changeover'));
        $tester->execute(['--date' => $date]);
        return $tester;
    }

    public function testPlaceNamesAreReported(): void
    {
        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('Chichester', $display);
        self::assertStringContainsString('origin', $display);
        self::assertStringContainsString('destination', $display);
        self::assertStringContainsString('2 value(s) on 1 trip(s) would fail', $display);
    }

    public function testValidPostcodesPass(): void
    {
        $this->updateTrip('PO19 1AA', 'BT7 1NN');
        $tester = $this->check();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        self::assertStringContainsString('No trips would fail', $tester->getDisplay());
    }

    public function testPartialPostcodesFollowTheConfiguredSetting(): void
    {
        $this->updateTrip('PO19', 'BT7');
        $tester = $this->check();

        self::assertSame(
            // @phpstan-ignore ternary.alwaysTrue (the constant is a product switch; the test must hold whichever way it's set)
            PostcodeChangeoverHelper::IRHS_ALLOW_PARTIAL_POSTCODE ? Command::SUCCESS : Command::FAILURE,
            $tester->getStatusCode(),
            $tester->getDisplay()
        );
    }

    public function testUnknownDistrictIsReportedWithOutwardCodeCheck(): void
    {
        $this->updateTrip('ZZ1 1AA', 'BT7 1NN');
        $tester = $this->check();

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('ZZ1 1AA', $tester->getDisplay());
        self::assertStringContainsString('outward-code-check: enabled', $tester->getDisplay());
    }

    public function testOutwardCodeCheckDisabledIsFlagged(): void
    {
        $this->setFeatures();
        $this->updateTrip('ZZ1 1AA', 'BT7 1NN');
        $tester = $this->check();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('outward-code-check: DISABLED', $tester->getDisplay());
    }

    public static function dataStates(): array
    {
        return [
            'new' => [SurveyStateInterface::STATE_NEW, true],
            'invited' => [SurveyStateInterface::STATE_INVITATION_SENT, true],
            'in progress' => [SurveyStateInterface::STATE_IN_PROGRESS, true],
            'closed (admin-editable)' => [SurveyStateInterface::STATE_CLOSED, true],
            'approved' => [SurveyStateInterface::STATE_APPROVED, false],
            'rejected' => [SurveyStateInterface::STATE_REJECTED, false],
            'exported' => [SurveyStateInterface::STATE_EXPORTED, false],
        ];
    }

    #[DataProvider('dataStates')]
    public function testOnlyEditableSurveysAreChecked(string $state, bool $expectedChecked): void
    {
        $this->updateTrip(state: $state);
        $tester = $this->check();

        self::assertSame($expectedChecked ? Command::FAILURE : Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
    }

    public function testSurveysBeforeTheDateAreNotChecked(): void
    {
        $tester = $this->check('2021-05-02');

        self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
    }

    public function testInvalidDateIsRejected(): void
    {
        $tester = $this->check('not-a-date');

        self::assertSame(Command::INVALID, $tester->getStatusCode());
    }

    public function testCommandDoesNotWrite(): void
    {
        $this->check();
        $this->entityManager->clear();

        $trip = $this->entityManager->getRepository(Trip::class)->findOneBy([]);
        self::assertSame('Chichester', $trip->getOrigin());
        self::assertSame('Chichester', $trip->getDestination());
    }
}
