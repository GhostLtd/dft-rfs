<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class DeliveriesTypeMapper extends DataMapper
{
    #[\Override]
    public function mapFormsToData(\Traversable $forms, &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $driverAvailability = $data->getSurvey()->getDriverAvailability();
        if ($driverAvailability->getHasMissedDeliveries() !== 'yes') {
            $driverAvailability->setNumberOfMissedDeliveries(null);
        }
    }
}
