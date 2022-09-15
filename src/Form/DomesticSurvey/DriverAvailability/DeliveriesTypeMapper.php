<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class DeliveriesTypeMapper extends PropertyPathMapper
{
    /**
     * @param FormInterface[]|Traversable $forms
     * @param SurveyResponse $data
     */
    public function mapFormsToData($forms, &$data)
    {
        parent::mapFormsToData($forms, $data);

        $driverAvailability = $data->getSurvey()->getDriverAvailability();
        if ($driverAvailability->getHasMissedDeliveries() !== 'yes') {
            $driverAvailability->setNumberOfMissedDeliveries(null);
        }
    }
}