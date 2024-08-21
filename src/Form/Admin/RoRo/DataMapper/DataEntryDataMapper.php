<?php

namespace App\Form\Admin\RoRo\DataMapper;

use App\Entity\RoRo\DataEntryDebugInfo;
use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use App\Form\RoRo\IntroductionType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Intl\Countries;

class DataEntryDataMapper implements DataMapperInterface
{
    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);

        /** @var FormInterface[] $forms */
        if (!$viewData instanceof Survey) {
            throw new Exception\UnexpectedTypeException($viewData, Survey::class);
        }

        $debugInfo = new DataEntryDebugInfo();
        $viewData->setDataEntryDebugInfo($debugInfo);

        $regex = "/".
            // Either:
            "(?:".
                // Country with optional code
                "(?:".
                    "(?:".
                        "(?P<country>(?:".                      // Country name:
                            "(?:[a-z\-\p{L}]{3,}|of|-|&)".      //   A word 3+ characters (including unicode letters),
                                                                //     the word "of", a dash, or an ampersand
                            "[\t\f ]*?".                        //   <whitespace> (optional, non-greedy)
                        ")+)".                                  //   <repeated as many times as possible>

                        "[\t\f ]+".                             // <whitespace>
                    ")".

                                                                // Country code (optional):
                    "(?:\(?".                                   //   <opening bracket> (optional)

                    // N.B. We allow a 3-digit code, in case the user enters a 3-digit code.
                    //      That won't match against our codes, but hopefully the country name will match.
                    //
                    //      (If we only allowed 2, then the row would be ignored and the sums not adding up would be
                    //       the only error the user received, so this is preferable).

                    "(?P<code>\w{2,3})".                        //   A code comprising 2 characters
                    "\)?\s+)?".                                 //   <closing bracket> (optional) followed by whitespace
                ")".
                // OR
                "|".
                // Just the country code:
                "(?:\(?(?P<code>\w{2,3})\)?\s+)".
            ")".

            // Followed by a count
            "(?P<count>[\d,]+)".
            "/imuJ";

        $monthHeaderRegex = "/".
            "Month \d+".
            "[\t\f ]+".
            "(?P<count>\d+)".
            "/imu";

        $oldCountryMap = [
            'bosnia' => 'bosnia & herzegovina',
            'czech republic' => 'czechia',
            'eire' => 'ireland',
            'fyr of macedonia' => 'north macedonia',
            'turkey' => 'türkiye',
        ];

        $hasAnyMatches = false;

        // Allows us to avoid wiping data if no matches were made.
        $clearDataIfFirstMatch = function() use ($viewData, &$hasAnyMatches) {
            if (!$hasAnyMatches) {
                // Clear any previous data
                foreach ($viewData->getVehicleCounts() as $vehicleCount) {
                    $vehicleCount->setVehicleCount(null);
                }
                $hasAnyMatches = true;
            }
        };

        $otherVehicleCount = $viewData->getVehicleCountByOtherCode(VehicleCount::OTHER_CODE_OTHER);

        // Breaking things into rows helps the regex cope with what sometimes ends up being backtracking hell, which
        // ultimately causes the parse to fail. It also allows us to pick out which rows were completely unparsable
        // for debugging purposes.
        foreach(explode("\n", $forms['data']->getData()) as $row) {
            if (preg_match($monthHeaderRegex, $row, $matches)) {
                // The "Month x 12345" headers found on the P&O import sheet
                // (denoting total powered vehicles for the month)
                $dataCount = intval(str_replace(',', '', $matches['count']));
                $debugInfo->setTotalVehicles($dataCount);
            } else if (preg_match_all($regex, $row, $matches, PREG_SET_ORDER)) {
                // Populate
                foreach ($matches as $match) {
                    [
                        'country' => $dataCountry,
                        'code' => $dataCountryCode,
                        'count' => $dataCount,
                    ] = $match;

                    $originalDataCountry = $dataCountry;
                    $originalDataCountryCode = $dataCountryCode;
                    $originalDataCount = $dataCount;

                    $dataCountry = mb_strtolower($dataCountry);
                    $dataCountry = mb_ereg_replace('-', ' ', $dataCountry);
                    $dataCountry = mb_ereg_replace('\s+', ' ', $dataCountry);

                    $dataCount = intval(str_replace(',', '', $dataCount));

                    if ($oldCountryMap[$dataCountry] ?? null) {
                        $dataCountry = $oldCountryMap[$dataCountry];
                    }

                    if (preg_match('/^([a-z]+)[\t ]+\1$/', $dataCountry, $countryMatch)) {
                        // "Kosovo" is being copied out of the spreadsheet as "Kosovo\t\t\tKosovo", even tho' there appears
                        // to be only one in the source cell.
                        $dataCountry = $countryMatch[1];
                    }

                    if (in_array($dataCountry, ['other country', 'other countries'])) {
                        $dataCountry = VehicleCount::OTHER_CODE_OTHER;
                    } elseif (in_array($dataCountry, ['unknown', 'unknown country', 'unknown countries'])) {
                        $dataCountry = VehicleCount::OTHER_CODE_UNKNOWN;
                    } elseif (in_array($dataCountry, ['unacc', 'unaccompanied', 'unaccompanied trailer', 'unaccompanied trailers'])) {
                        $dataCountry = VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS;
                    }

                    $found = false;
                    foreach ($viewData->getVehicleCounts() as $vehicleCount) {
                        $countryCode = $vehicleCount->getCountryCode();
                        $label = $vehicleCount->getLabel();
                        $label = $label !== null ? mb_strtolower($label) : null;

                        if ($countryCode === $dataCountryCode ||
                            $label === $dataCountry ||
                            $vehicleCount->getOtherCode() === $dataCountry
                        ) {
                            $clearDataIfFirstMatch();
                            $vehicleCount->setVehicleCount($dataCount);
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        if (
                            str_ends_with($dataCountry, 'total powered vehicles') ||
                            $dataCountry === 'overall result'
                        ) {
                            $debugInfo->setTotalPoweredVehicles($dataCount);
                        } else if (str_ends_with($dataCountry, 'total vehicles')) {
                            $debugInfo->setTotalVehicles($dataCount);
                        } else {
                            $clearDataIfFirstMatch();
                            $foundOtherCountry = false;

                            foreach(Countries::getCountryCodes() as $countryCode) {
                                if ($dataCountryCode === $countryCode) {
                                    $foundOtherCountry = true;
                                    break;
                                }
                            }

                            if (!$foundOtherCountry) {
                                foreach(Countries::getNames('en') as $countryName) {
                                    if ($dataCountry === strtolower($countryName)) {
                                        $foundOtherCountry = true;
                                        break;
                                    }
                                }
                            }

                            if ($foundOtherCountry) {
                                $otherVehicleCount->setVehicleCount(
                                    ($otherVehicleCount->getVehicleCount() ?? 0) +
                                    $dataCount
                                );
                            } else {
                                $debugInfo->addUnusedRow($originalDataCountry, $originalDataCountryCode, $originalDataCount);
                            }
                        }
                    }
                }

                $viewData->setDataEntryMethod(IntroductionType::DATA_ENTRY_ADVANCED_CHOICE);
            }
            else {
                if (trim($row) !== '') {
                    $debugInfo->addUnparsableRow($row);
                }
            }
        }

        if (!$hasAnyMatches) {
            throw new Exception\TransformationFailedException('Unable to parse data', 0, null, 'roro.survey.advanced-data-entry.invalid');
        }
    }
}
