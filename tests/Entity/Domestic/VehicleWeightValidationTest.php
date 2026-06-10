<?php

namespace App\Tests\Entity\Domestic;

use App\Entity\Domestic\Vehicle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validation of the CSRGT vehicle weights step (group "vehicle_weight"): gross weight,
 * carrying capacity, and the cross-field "capacity not more than gross weight" rule.
 */
class VehicleWeightValidationTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
    }

    public function testNonPositiveGrossWeightShowsOnlyTheMinimumError(): void
    {
        $messages = $this->violations((new Vehicle())->setGrossWeight(-5));

        $this->assertContains('grossWeight:common.vehicle.gross-weight.minimum', $messages);
        // The minimum (3500) already rejects non-positive values, so the redundant Positive
        // constraint must not also fire and produce a second, confusing error.
        $this->assertNotContains('grossWeight:common.number.positive', $messages);
    }

    public function testNonPositiveCarryingCapacityShowsOnlyTheMinimumError(): void
    {
        $messages = $this->violations(
            (new Vehicle())->setGrossWeight(10000)->setCarryingCapacity(-5)
        );

        $this->assertContains('carryingCapacity:common.vehicle.carrying-capacity.minimum', $messages);
        $this->assertNotContains('carryingCapacity:common.number.positive', $messages);
    }

    public function testBlankGrossWeightDoesNotTriggerCrossFieldError(): void
    {
        // Gross weight left blank, carrying capacity filled: the only complaint should be that
        // gross weight is required — not a misleading "capacity is more than gross weight".
        $messages = $this->violations((new Vehicle())->setCarryingCapacity(1500));

        $this->assertContains('grossWeight:common.vehicle.gross-weight.not-blank', $messages);
        $this->assertNotContains(
            'carryingCapacity:common.vehicle.carrying-capacity.less-than-gross-weight',
            $messages
        );
    }

    public function testCrossFieldErrorStillFiresWhenCapacityExceedsGrossWeight(): void
    {
        $messages = $this->violations(
            (new Vehicle())->setGrossWeight(5000)->setCarryingCapacity(6000)
        );

        $this->assertContains(
            'carryingCapacity:common.vehicle.carrying-capacity.less-than-gross-weight',
            $messages
        );
    }

    public function testValidWeightsProduceNoViolations(): void
    {
        $messages = $this->violations(
            (new Vehicle())->setGrossWeight(10000)->setCarryingCapacity(2000)
        );

        $this->assertSame([], $messages);
    }

    /** @return string[] "propertyPath:messageTemplate" for each violation in the vehicle_weight group */
    private function violations(Vehicle $vehicle): array
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $messages = [];
        foreach ($validator->validate($vehicle, null, ['vehicle_weight']) as $violation) {
            $messages[] = $violation->getPropertyPath() . ':' . $violation->getMessageTemplate();
        }

        return $messages;
    }
}
