<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class DriversAndVacanciesTypeMapper extends DataMapper
{
    #[\Override]
    public function mapFormsToData(\Traversable $forms, &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $driverAvailability = $data->getSurvey()->getDriverAvailability();
        if ($driverAvailability->getHasVacancies() !== 'yes') {
            // conditional fields on this form
            $driverAvailability
                ->setNumberOfDriverVacancies(null)
                ->setReasonsForDriverVacancies(null);

            // fields on conditional deliveries form
            $driverAvailability
                ->setNumberOfLorriesOperated(null)
                ->setNumberOfParkedLorries(null)
                ->setHasMissedDeliveries(null)
                ->setNumberOfMissedDeliveries(null);
        }

        $reasonsForDriverVacancies = $driverAvailability->getReasonsForDriverVacancies();
        if ($reasonsForDriverVacancies === null || !in_array('other', $reasonsForDriverVacancies)) {
            $driverAvailability->setReasonsForDriverVacanciesOther(null);
        }
    }
}
