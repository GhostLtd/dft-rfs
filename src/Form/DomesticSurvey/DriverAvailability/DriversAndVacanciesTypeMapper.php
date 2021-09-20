<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class DriversAndVacanciesTypeMapper extends PropertyPathMapper
{
    /**
     * @param FormInterface[]|Traversable $forms
     * @param SurveyResponse $data
     */
    public function mapFormsToData($forms, &$data)
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