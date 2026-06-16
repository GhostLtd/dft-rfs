<?php

namespace App\Tests\Form\Type\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\VehicleWeightsType;
use App\Form\Extension\ConvertTonnesExtension;
use App\Form\Extension\StripThousandsSeparatorsExtension;
use App\Form\Extension\StripUnitsExtension;
use App\Tests\Form\Type\AbstractTypeTestCase;
use App\Utility\Form\ThousandsSeparatorNormaliser;
use App\Utility\Form\TonnesToKilogramsNormaliser;
use App\Utility\Form\UnitStripper;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\FormInterface;

class VehicleWeightsTypeTest extends AbstractTypeTestCase
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

    public function testMixedSeparatorsAreLeftToFailValidation(): void
    {
        $response = $this->submitWeights('188,188.188', '2000');

        // Not stripped → the integer transformer rejects it.
        $this->assertFalse($response['form']->get('grossWeight')->isSynchronized());
        $this->assertFalse($response['form']->isValid());
    }

    public function testUnconfiguredUnitIsLeftToFailValidation(): void
    {
        $response = $this->submitWeights('3500cc', '2000');

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

    public function testTonnesAreConvertedToKilograms(): void
    {
        // "44 T" -> 44000, "3,5 TON" -> 3500 (comma read as a decimal point, x1000).
        $response = $this->submitWeights('44 T', '3,5 TON');

        $this->assertTrue($response['form']->isValid());
        $this->assertSame(44000, $response['vehicle']->getGrossWeight());
        $this->assertSame(3500, $response['vehicle']->getCarryingCapacity());
    }

    public function testDecimalTonnesConvertToKilograms(): void
    {
        $response = $this->submitWeights('26.5 tonnes', '3.8 T');

        $this->assertTrue($response['form']->isValid());
        $this->assertSame(26500, $response['vehicle']->getGrossWeight());
        $this->assertSame(3800, $response['vehicle']->getCarryingCapacity());
    }

    public function testTonnesAboveThresholdAreLeftToFailValidation(): void
    {
        // 150 is not < 100, so it is not treated as tonnes; "150 T" is not a bare integer either.
        $response = $this->submitWeights('150 T', '2000');

        $this->assertFalse($response['form']->get('grossWeight')->isSynchronized());
        $this->assertFalse($response['form']->isValid());
    }

    public function testWithoutTheOptionsAnIntegerFieldStillRejectsSeparators(): void
    {
        // Control: a bare Gds IntegerType (options default to off) does not strip.
        $form = $this->factory->create(IntegerType::class);
        $form->submit('1,000');

        $this->assertFalse($form->isSynchronized());
    }

    /**
     * @return array{form: FormInterface, vehicle: Vehicle}
     */
    private function submitWeights(string $grossWeight, string $carryingCapacity): array
    {
        $vehicle = new Vehicle();
        $response = (new SurveyResponse())->setVehicle($vehicle);

        $form = $this->factory->create(VehicleWeightsType::class, $response);
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
