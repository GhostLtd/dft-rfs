<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\CurrencyOrPercentage;
use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class WagesTypeMapper extends PropertyPathMapper
{
    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$data instanceof SurveyResponse) {
            throw new UnexpectedTypeException($data, SurveyResponse::class);
        }

        $driverAvailability = $data->getSurvey()->getDriverAvailability();

        if ($driverAvailability && isset($forms['haveWagesIncreased'])) {
            $wageIncrease = $driverAvailability->getAverageWageIncrease();
            $wageIncreaseValue = $wageIncrease ? $wageIncrease->getValue() : null;

            if ($wageIncrease && $wageIncrease->getUnit() === CurrencyOrPercentage::UNIT_PERCENTAGE) {
                $wageIncreaseValue = null; // This option is no longer allowed
            }

            $forms['haveWagesIncreased']->setData($driverAvailability->getHaveWagesIncreased());
            $forms['averageWageIncrease']->setData($wageIncreaseValue);
            $forms['wageIncreasePeriod']->setData($driverAvailability->getWageIncreasePeriod());
            $forms['wageIncreasePeriodOther']->setData($driverAvailability->getWageIncreasePeriodOther());
            $forms['reasonsForWageIncrease']->setData($driverAvailability->getReasonsForWageIncrease());
            $forms['reasonsForWageIncreaseOther']->setData($driverAvailability->getReasonsForWageIncreaseOther());
        }
    }

    /**
     * @param FormInterface[]|Traversable $forms
     * @param SurveyResponse $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$data instanceof SurveyResponse) {
            throw new UnexpectedTypeException($data, SurveyResponse::class);
        }

        $driverAvailability = $data->getSurvey()->getDriverAvailability();

        $wageIncreaseValue = $forms['averageWageIncrease']->getData();
        $wageIncrease = $wageIncreaseValue ?
            (new CurrencyOrPercentage())
                ->setUnit(CurrencyOrPercentage::UNIT_CURRENCY)
                ->setValue($wageIncreaseValue) :
            null;

        $driverAvailability
            ->setHaveWagesIncreased($forms['haveWagesIncreased']->getData())
            ->setAverageWageIncrease($wageIncrease)
            ->setWageIncreasePeriod($forms['wageIncreasePeriod']->getData())
            ->setWageIncreasePeriodOther($forms['wageIncreasePeriodOther']->getData())
            ->setReasonsForWageIncrease($forms['reasonsForWageIncrease']->getData())
            ->setReasonsForWageIncreaseOther($forms['reasonsForWageIncreaseOther']->getData());

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