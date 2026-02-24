<?php

namespace App\Command\Dev\DriverAvailability;

use App\Command\Dev\AbstractTableExportComparator;
use App\Entity\Domestic\DriverAvailability;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DriverAvailabilityTableComparator extends AbstractTableExportComparator
{
    public function __construct(SymfonyStyle $io, EntityManagerInterface $entityManager)
    {
        parent::__construct($io, $entityManager);
    }

    public function getExportTableName(): string
    {
        return 'driver_availability';
    }

    public function getDatabaseIdColumns(): array
    {
        return ['RegistrationMark', 'SubmissionDate'];
    }

    public function getExportIdColumns(): array
    {
        return ['registrationMark', 'submissionDate'];
    }

    public function getRowsFromDatabase(int $year, int $month): Result
    {
        $sql = 'SELECT * FROM view_export_domestic_driver_availability';
        $params = [];
        $types = [];

        if ($year !== 0 && $month !== 0) {
            $sql .= ' WHERE SubmissionYear = :year AND SubmissionMonth = :month';
            $params['year'] = $year;
            $params['month'] = $month;
            $types['year'] = Types::INTEGER;
            $types['month'] = Types::INTEGER;
        }

        return $this->entityManager->getConnection()
            ->executeQuery($sql, $params, $types);
    }

    public function checkRow(array $dbRow, array $exportRow): void
    {
        // CSV-only columns - mark as seen but don't compare
        $this->columnWatcher
            ->columnSeen('businessNature')
            ->columnSeen('numberOfEmployees')
            ->columnSeen('yearNumber');

        // Survey columns
        $this->checkStringColumn('state', $this->getColumn($exportRow, 'state'), $dbRow['State']);
        $this->checkIntegerColumn('isNorthernIreland', intval($this->getColumn($exportRow, 'isNorthernIreland')), intval($dbRow['IsNorthernIreland']));
        $this->checkIntegerColumn('weekNumber', $this->csvToNullableInt($this->getColumn($exportRow, 'weekNumber')), $this->dbToNullableInt($dbRow['WeekNumber']));
        $this->checkStringColumn('businessName', $this->csvEmptyToNull($this->getColumn($exportRow, 'businessName')), $dbRow['BusinessName']);

        // String columns (CSV empty strings are equivalent to DB nulls)
        $this->checkStringColumn('registrationMark', $this->getColumn($exportRow, 'registrationMark'), $dbRow['RegistrationMark']);
        $this->checkStringColumn('submissionDate', $this->getColumn($exportRow, 'submissionDate'), $dbRow['SubmissionDate']);
        $this->checkStringColumn('hasVacancies', $this->csvEmptyToNull($this->getColumn($exportRow, 'hasVacancies')), $dbRow['HasVacancies']);
        $this->checkStringColumn('reasonsForDriverVacanciesOther', $this->csvEmptyToNull($this->getColumn($exportRow, 'reasonsForDriverVacanciesOther')), $dbRow['ReasonsForDriverVacanciesOther']);
        $this->checkStringColumn('haveWagesIncreased', $this->csvEmptyToNull($this->getColumn($exportRow, 'haveWagesIncreased')), $dbRow['HaveWagesIncreased']);
        $this->checkStringColumn('reasonsForWageIncreaseOther', $this->csvEmptyToNull($this->getColumn($exportRow, 'reasonsForWageIncreaseOther')), $dbRow['ReasonsForWageIncreaseOther']);
        $this->checkStringColumn('hasPaidBonus', $this->csvEmptyToNull($this->getColumn($exportRow, 'hasPaidBonus')), $dbRow['HasPaidBonus']);
        $this->checkStringColumn('hasMissedDeliveries', $this->csvEmptyToNull($this->getColumn($exportRow, 'hasMissedDeliveries')), $dbRow['HasMissedDeliveries']);
        $this->checkStringColumn('wageIncreasePeriod', $this->csvEmptyToNull($this->getColumn($exportRow, 'wageIncreasePeriod')), $dbRow['WageIncreasePeriod']);
        $this->checkStringColumn('wageIncreasePeriodOther', $this->csvEmptyToNull($this->getColumn($exportRow, 'wageIncreasePeriodOther')), $dbRow['WageIncreasePeriodOther']);

        // Integer columns
        $this->checkIntegerColumn('numberOfDriversEmployed', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfDriversEmployed')), $this->dbToNullableInt($dbRow['NumberOfDriversEmployed']));
        $this->checkIntegerColumn('numberOfDriverVacancies', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfDriverVacancies')), $this->dbToNullableInt($dbRow['NumberOfDriverVacancies']));
        $this->checkIntegerColumn('numberOfDriversThatHaveLeft', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfDriversThatHaveLeft')), $this->dbToNullableInt($dbRow['NumberOfDriversThatHaveLeft']));
        $this->checkIntegerColumn('numberOfLorriesOperated', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfLorriesOperated')), $this->dbToNullableInt($dbRow['NumberOfLorriesOperated']));
        $this->checkIntegerColumn('numberOfParkedLorries', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfParkedLorries')), $this->dbToNullableInt($dbRow['NumberOfParkedLorries']));
        $this->checkIntegerColumn('numberOfMissedDeliveries', $this->csvToNullableInt($this->getColumn($exportRow, 'numberOfMissedDeliveries')), $this->dbToNullableInt($dbRow['NumberOfMissedDeliveries']));

        // Monetary columns (DB stores pence, CSV has pounds formatted to 2dp)
        $this->checkMoneyColumn('averageWageIncrease', $this->getColumn($exportRow, 'averageWageIncrease'), $dbRow['AverageWageIncrease']);
        $this->checkMoneyColumn('averageBonus', $this->getColumn($exportRow, 'averageBonus'), $dbRow['AverageBonus']);

        // Expanded JSON array columns
        $this->checkExpandedJsonArrayColumn($exportRow, 'reasonsForDriverVacancies', $dbRow['ReasonsForDriverVacancies'], DriverAvailability::VACANCY_REASON_CHOICES);
        $this->checkExpandedJsonArrayColumn($exportRow, 'reasonsForWageIncrease', $dbRow['ReasonsForWageIncrease'], DriverAvailability::WAGE_INCREASE_REASON_CHOICES);
        $this->checkExpandedJsonArrayColumn($exportRow, 'reasonsForBonuses', $dbRow['ReasonsForBonuses'], DriverAvailability::BONUS_REASON_CHOICES);
    }

    private function csvEmptyToNull(mixed $value): ?string
    {
        return ($value === '' || $value === null) ? null : $value;
    }

    private function csvToNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return intval($value);
    }
}
