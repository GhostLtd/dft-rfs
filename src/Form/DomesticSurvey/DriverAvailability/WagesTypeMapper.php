<?php


namespace App\Form\DomesticSurvey\DriverAvailability;


use App\Entity\Domestic\SurveyResponse;
use App\Utility\Domestic\DriverAvailabilityExportHelper;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class WagesTypeMapper extends PropertyPathMapper
{
    /**
     * @param FormInterface[]|Traversable $forms
     * @param SurveyResponse $data
     */
    public function mapFormsToData($forms, &$data)
    {
        parent::mapFormsToData($forms, $data);

        $driverAvailability = $data->getSurvey()->getDriverAvailability();
        if ($driverAvailability->getHaveWagesIncreased() !== 'yes') {
            $driverAvailability
                ->setAverageWageIncrease(null)
                ->setReasonsForWageIncrease(null)
                ->setReasonsForWageIncreaseOther(null)
                ->setWageIncreasePeriod(null)
                ->setWageIncreasePeriodOther(null);
        }

        $reasonsForWageIncrease = $driverAvailability->getReasonsForWageIncrease();
        if ($reasonsForWageIncrease === null || !in_array('other', $reasonsForWageIncrease)) {
            $driverAvailability->setReasonsForWageIncreaseOther(null);
        }

        $wageIncreasePeriod = $driverAvailability->getWageIncreasePeriod();
        if ($wageIncreasePeriod !== 'other') {
            $driverAvailability->setWageIncreasePeriodOther(null);
        }
    }
}