<?php

namespace App\Tests\Form\Type\Admin\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Entity\Vehicle as VehicleLookup;
use App\Form\Admin\InternationalSurvey\TripType;
use App\Form\Admin\InternationalSurvey\VehicleType;
use App\Form\Extension\ConvertTonnesExtension;
use App\Form\Extension\StripThousandsSeparatorsExtension;
use App\Form\Extension\StripUnitsExtension;
use App\Form\Validator\ValidRegistrationValidator;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Form\Type\AbstractTypeTest;
use App\Utility\Form\ThousandsSeparatorNormaliser;
use App\Utility\Form\TonnesToKilogramsNormaliser;
use App\Utility\Form\UnitStripper;
use App\Utility\PortsDataFactory;
use App\Utility\PortsDataSet;
use Symfony\Component\Form\PreloadedExtension;

/**
 * The IRHS admin vehicle and trip forms take the same weight inputs as the public forms,
 * so they get the same input normalisation (strip units/separators, convert tonnes).
 */
class VehicleWeightNormalisationTest extends AbstractTypeTest
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

    #[\Override]
    protected function getValidators(): array
    {
        // The class-level ValidRegistration constraint (admin_vehicle group) needs its
        // validator; the registration mark is null here so the EM is never touched.
        return array_merge(parent::getValidators(), [
            ValidRegistrationValidator::class => new ValidRegistrationValidator($this->createMock(EntityManagerInterface::class)),
        ]);
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $portsDataSet = $this->createMock(PortsDataSet::class);
        $portsDataSet->method('getPorts')->willReturn([]);
        $portsDataSet->method('getPortChoices')->willReturn([]);

        $portsDataFactory = $this->createMock(PortsDataFactory::class);
        $portsDataFactory->method('getData')->willReturn($portsDataSet);

        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([new TripType($portsDataFactory)], []),
        ]);
    }

    public function testAdminVehicleFormNormalisesWeightInputs(): void
    {
        $vehicle = new Vehicle();

        $form = $this->factory->create(VehicleType::class, $vehicle);
        $form->submit([
            'grossWeight' => '44,000 kg',
            'carryingCapacity' => '20 T',
        ]);

        $this->assertTrue($form->get('grossWeight')->isSynchronized());
        $this->assertTrue($form->get('carryingCapacity')->isSynchronized());
        $this->assertSame(44000, $vehicle->getGrossWeight());
        $this->assertSame(20000, $vehicle->getCarryingCapacity());
    }

    public function testAdminTripFormWeightFieldsOptIntoNormalisation(): void
    {
        $form = $this->factory->create(TripType::class, $this->buildTrip());

        foreach (['gross_weight', 'carrying_capacity'] as $field) {
            $config = $form->get('otherDetails')->get($field)->getConfig();

            $this->assertTrue($config->getOption('strip_thousands_separators'), "$field should strip separators");
            $this->assertSame(['kg'], $config->getOption('strip_units'), "$field should strip kg units");
            $this->assertTrue($config->getOption('convert_tonnes'), "$field should convert tonnes");
        }
    }

    private function buildTrip(): Trip
    {
        // Survey period after 2024-09-17 → trip distance disabled → simplest admin form shape.
        $survey = (new Survey())
            ->setSurveyPeriodStart(new \DateTime('2026-01-01'))
            ->setSurveyPeriodEnd(new \DateTime('2026-01-14'));
        $response = new SurveyResponse();
        $survey->setResponse($response);

        $vehicle = (new Vehicle())
            ->setTrailerConfiguration(VehicleLookup::TRAILER_CONFIGURATION_ARTICULATED)
            ->setAxleConfiguration(333);
        $response->addVehicle($vehicle);

        return (new Trip())
            ->setVehicle($vehicle)
            ->setIsSwappedTrailer(true);
    }
}
