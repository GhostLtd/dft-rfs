<?php

namespace App\Form\DomesticSurvey\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class VehicleFuelTypeMapper extends DataMapper
{
    /**
     * @param SurveyResponse $data
     * @param \Traversable $forms
     * @return void
     */
    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        parent::mapDataToForms($data, $forms);
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (count($forms) === 1) return;

        $vehicle = $data->getVehicle();
        switch ($type = $vehicle->getFuelType()) {
            case null:
                break;

            case 'petrol':
            case 'diesel':
                $forms["fuelQuantity_$type"]->setData($vehicle->getFuelQuantity());
                $forms["fuelTypeIsHybrid_$type"]->setData($vehicle->getIsFuelTypeHybrid());
                break;

            case 'other':
                $forms["fuelQuantity_other"]->setData($vehicle->getFuelQuantityOther());
                break;

            default:
                $forms["fuelQuantity_$type"]->setData($vehicle->getFuelQuantity());
        }
    }

    /**
     * @param \Traversable $forms
     * @param SurveyResponse $data
     * @return void
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        parent::mapFormsToData($forms, $data);
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (count($forms) === 1) return;

        $vehicle = $data->getVehicle();
        switch ($type = $vehicle->getFuelType()) {
            case null :
                $vehicle
                    ->setFuelQuantity(null)
                    ->setFuelQuantityOther(null)
                    ->setIsFuelTypeHybrid(null);
                ;
                break;

            case 'petrol':
            case 'diesel':
                $vehicle
                    ->setFuelQuantity($forms["fuelQuantity_$type"]->getData())
                    ->setFuelQuantityOther(null)
                    ->setIsFuelTypeHybrid($forms["fuelTypeIsHybrid_$type"]->getData());
                break;

            case 'other':
                $vehicle
                    ->setFuelQuantity(null)
                    ->setFuelQuantityOther($forms["fuelQuantity_other"]->getData())
                    ->setIsFuelTypeHybrid(null);
                break;

            default:
                $vehicle
                    ->setFuelQuantity($forms["fuelQuantity_$type"]->getData())
                    ->setFuelQuantityOther(null)
                    ->setIsFuelTypeHybrid(null);
        }
    }
}