<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class WagesTypeMapper implements DataMapperInterface
{
    #[\Override]
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $driverAvailability = $viewData->getSurvey()->getDriverAvailability();

        if ($driverAvailability && isset($forms['haveWagesIncreased'])) {
            $forms['haveWagesIncreased']->setData($driverAvailability->getHaveWagesIncreased());
            $forms['averageWageIncrease']->setData($driverAvailability->getAverageWageIncrease());
            $forms['wageIncreasePeriod']->setData($driverAvailability->getWageIncreasePeriod());
            $forms['wageIncreasePeriodOther']->setData($driverAvailability->getWageIncreasePeriodOther());
            $forms['reasonsForWageIncrease']->setData($driverAvailability->getReasonsForWageIncrease());
            $forms['reasonsForWageIncreaseOther']->setData($driverAvailability->getReasonsForWageIncreaseOther());
        }
    }

    #[\Override]
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $driverAvailability = $viewData->getSurvey()->getDriverAvailability();

        $driverAvailability
            ->setHaveWagesIncreased($forms['haveWagesIncreased']->getData())
            ->setAverageWageIncrease($forms['averageWageIncrease']->getData())
            ->setWageIncreasePeriod($forms['wageIncreasePeriod']->getData())
            ->setWageIncreasePeriodOther($forms['wageIncreasePeriodOther']->getData())
            ->setReasonsForWageIncrease($forms['reasonsForWageIncrease']->getData())
            ->setReasonsForWageIncreaseOther($forms['reasonsForWageIncreaseOther']->getData());

        // Clear legacy field
        $driverAvailability->setLegacyAverageWageIncreasePercentage(null);

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
