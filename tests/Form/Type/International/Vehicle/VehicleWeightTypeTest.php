<?php

namespace App\Tests\Form\Type\International\Vehicle;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Form\Extension\ConvertTonnesExtension;
use App\Form\Extension\StripThousandsSeparatorsExtension;
use App\Form\Extension\StripUnitsExtension;
use App\Form\InternationalSurvey\Trip\VehicleWeightType as TripVehicleWeightType;
use App\Form\InternationalSurvey\Vehicle\VehicleWeightType;
use App\Tests\Form\Type\AbstractTypeTest;
use App\Utility\Form\ThousandsSeparatorNormaliser;
use App\Utility\Form\TonnesToKilogramsNormaliser;
use App\Utility\Form\UnitStripper;
use Symfony\Component\Form\FormInterface;

/**
 * IRHS port of the CSRGT vehicle-weights input normalisation: strip "kg" units and
 * comma/dot thousands separators, and convert explicitly tonnes-denominated values to
 * kilograms (common user errors — see docs/features/2026-06-10-vehicle-weights-research.md).
 */
class VehicleWeightTypeTest extends AbstractTypeTest
{
    #[\Override]
    protected function getTypeExtensions(): array
    {
        return array_merge(parent::getTypeExtensions(), [
            new ConvertTonnesExtension(new TonnesToKilogramsNormaliser()),
            new StripUnitsExtension(new UnitStripper()),
            new StripThousandsSeparatorsExtension(new ThousandsSeparatorNormaliser()),
        ]);
    }

    public function testCommaThousandsSeparatorsAreStrippedAndAccepted(): void
    {
        $response = $this->submitWeights('44,000', '20,000');

        $this->assertTrue($response['form']->isSynchronized());
        $this->assertTrue($response['form']->isValid());
        $this->assertSame(44000, $response['vehicle']->getGrossWeight());
        $this->assertSame(20000, $response['vehicle']->getCarryingCapacity());
    }

    public function testUnitsAndSeparatorsCompose(): void
    {
        // Units strip first ("3,500 kg" -> "3,500"), then separators ("3,500" -> 3500).
        $response = $this->submitWeights('3,500 kg', '1,200kg');

        $this->assertTrue($response['form']->isValid());
        $this->assertSame(3500, $response['vehicle']->getGrossWeight());
        $this->assertSame(1200, $response['vehicle']->getCarryingCapacity());
    }

    public function testDotThousandsSeparatorIsStripped(): void
    {
        $response = $this->submitWeights('7.500', '4.000');

        $this->assertTrue($response['form']->isValid());
        $this->assertSame(7500, $response['vehicle']->getGrossWeight());
        $this->assertSame(4000, $response['vehicle']->getCarryingCapacity());
    }

    public function testTonnesAreConvertedToKilograms(): void
    {
        $response = $this->submitWeights('26.5 tonnes', '3.8 T');

        $this->assertTrue($response['form']->isValid());
        $this->assertSame(26500, $response['vehicle']->getGrossWeight());
        $this->assertSame(3800, $response['vehicle']->getCarryingCapacity());
    }

    public function testMixedSeparatorsAreLeftToFailValidation(): void
    {
        $response = $this->submitWeights('188,188.188', '2000');

        // Not stripped → the integer transformer rejects it.
        $this->assertFalse($response['form']->get('grossWeight')->isSynchronized());
        $this->assertFalse($response['form']->isValid());
    }

    public function testCrossFieldComparisonRunsOnceSeparatorInputParses(): void
    {
        // Both parse to real ints, so the "capacity must be less than gross weight" rule now fires.
        $response = $this->submitWeights('4,000', '5,000');

        $this->assertFalse($response['form']->isValid());
        $this->assertContains(
            'common.vehicle.carrying-capacity.less-than-gross-weight',
            $this->errorTemplates($response['form'])
        );
    }

    public function testTripVariantNormalisesToo(): void
    {
        // The trip swapped-trailer weights step extends the vehicle form and must inherit the
        // same input normalisation.
        $trip = new Trip();

        $form = $this->factory->create(TripVehicleWeightType::class, $trip);
        $form->submit(['grossWeight' => '44,000 kg', 'carryingCapacity' => '20 T']);

        $this->assertTrue($form->isValid());
        $this->assertSame(44000, $trip->getGrossWeight());
        $this->assertSame(20000, $trip->getCarryingCapacity());
    }

    /**
     * @return array{form: FormInterface, vehicle: Vehicle}
     */
    private function submitWeights(string $grossWeight, string $carryingCapacity): array
    {
        $vehicle = new Vehicle();

        $form = $this->factory->create(VehicleWeightType::class, $vehicle);
        $form->submit(['grossWeight' => $grossWeight, 'carryingCapacity' => $carryingCapacity]);

        return ['form' => $form, 'vehicle' => $vehicle];
    }

    /** @return string[] */
    private function errorTemplates(FormInterface $form): array
    {
        $templates = [];
        foreach ($form->getErrors(true) as $error) {
            $templates[] = $error->getMessageTemplate();
        }

        return $templates;
    }
}
