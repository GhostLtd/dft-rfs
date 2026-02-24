<?php

namespace App\Command\Dev;

use App\Utility\Quarter\NaturalQuarterHelper;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Intl\Countries;

abstract class AbstractTableExportComparator
{
    protected array $countries;
    protected array $columnIdxToNames;
    protected array $columnNamesToIdx;
    protected ColumnWatcher $columnWatcher;
    protected ErrorReporter $errorReporter;

    abstract public function getExportTableName(): string;
    abstract public function getDatabaseIdColumns(): array;
    abstract public function getExportIdColumns(): array;

    abstract public function getRowsFromDatabase(int $year, int $quarter): Result;
    abstract public function checkRow(array $dbRow, array $exportRow): void;

    public function __construct(
        protected SymfonyStyle           $io,
        protected EntityManagerInterface $entityManager,
    ) {
        $this->errorReporter = new ErrorReporter($io, $this->getExportTableName());

        $this->countries = array_flip(array_map(strtoupper(...), Countries::getNames()));
        $this->countries['CZECH REPUBLIC'] = 'CZ';
    }

    public function compare(
        array     $columnNamesToIdx,
        array     $exportData,
        int       $year,
        int       $quarter,
    ): void {
        $this->columnWatcher = new ColumnWatcher(array_keys($columnNamesToIdx));
        $this->columnNamesToIdx = $columnNamesToIdx;
        $this->columnIdxToNames = array_flip($columnNamesToIdx);

        $exportCount = count($exportData);
        $result = $this->getRowsFromDatabase($year, $quarter);

        $this->io->writeln("<info>{$this->getExportTableName()}</info>: Database <info>{$result->rowCount()}</info> rows, Export <info>{$exportCount}</info> rows");

        $columnsChecked = false;
        $idColumns = $this->getDatabaseIdColumns();
        foreach($result->iterateAssociative() as $dbRow) {
            $id = join('-', array_map(fn(string $c) => $dbRow[$c], $idColumns));
            $this->errorReporter->setContext($id);

            $exportRow = $exportData[$id] ?? null;
            if (!$exportRow) {
                $this->errorReporter->error("Could not find match for ID");
                return;
            }

            $this->checkRow($dbRow, $exportRow);

            if (!$columnsChecked) {
                $columnsChecked = true;
                $unseedColumns = $this->columnWatcher->getUnseenColumns();

                if ($unseedColumns) {
                    $this->errorReporter->error("Not all columns matched on export: {$unseedColumns}");
                }
            }

            unset($exportData[$id]);
        }

        if (!empty($exportData)) {
            $this->errorReporter->setContext(null);
            $this->errorReporter->error("Not all data matched");
//            dump(array_map(fn(array $x) => array_combine(array_keys($this->columnNamesToIdx), $x), $exportData));
        }
    }

    protected function checkBitmapColumnMatches(array &$exportRow, ?string $dbValue, array $map): void
    {
        foreach($map as $dbColumn => $exportColumn) {
            $expectedValue = $dbColumn === $dbValue;
            $exportValue = $this->getColumn($exportRow, $exportColumn);

            $this->columnWatcher->columnSeen($exportColumn);

            if ($expectedValue !== ($exportValue === '1')) {
                $this->errorReporter->error("Bitmap mismatch - DB: '{$dbValue}' mismatch @ enum {$dbColumn} - export {$exportColumn} = {$exportValue}");
            }
        }
    }

    protected function getColumn(array $data, string $columnName): mixed
    {
        $key = $this->columnNamesToIdx[$columnName] ?? null;

        if ($key === null) {
            debug_print_backtrace();
            $this->errorReporter->error("Unknown key in export data: {$columnName}: {$key}");
            $this->io->table(['Names'], $this->columnNamesToIdx);
            exit;
        }

        if (!array_key_exists($key, $data)) {
            $this->errorReporter->error("Not found in export data: {$columnName}");
            dump($data);
            exit;
        }

        return $data[$key];
    }

    protected function checkArrayColumn(string $exportColumnName, ?array $exportValue, ?array $dbValue): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        $dbStr = json_encode($dbValue);
        $exportStr = json_encode($exportValue);
        if ($dbStr !== $exportStr) {
            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportStr}, got [db] {$dbStr}");
        }
    }

    protected function checkStringColumn(string $exportColumnName, ?string $exportValue, ?string $dbValue, bool $transliterate = false, bool $replaceCrLf = true): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        // Transliterate (the exports were)
        if ($transliterate && $dbValue !== null) {
            $dbValue = iconv('UTF-8', 'ASCII//TRANSLIT', $dbValue);
            $dbValue = preg_replace('/[^\x{0020}-\x{007f}\n\r\t]/u', '?', $dbValue);
        }

        if ($replaceCrLf && $exportValue !== null) {
            $exportValue = str_replace('+@cr+', "\r", $exportValue);
            $exportValue = str_replace('+@lf+', "\n", $exportValue);
        }

        if ($exportValue !== $dbValue) {
            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportValue}, got [db] {$dbValue}");
        }
    }

    protected function checkIntegerColumn(string $exportColumnName, ?int $exportValue, ?int $dbValue): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        if ($exportValue !== $dbValue) {
            if ($dbValue === null) {
                $dbValue = '<NULL>';
            }

            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportValue}, got [db] {$dbValue}");
        }
    }

    protected function checkDecimalColumn(string $exportColumnName, ?string $exportValue, ?string $dbValue): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        if ($exportValue === null && $dbValue === null) {
            return;
        }

        if ($exportValue !== null && $dbValue !== null) {
            $exportValue = BigDecimal::of($exportValue);
            $dbValue = BigDecimal::of($dbValue);

            if (!$exportValue->isEqualTo($dbValue)) {
                $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportValue}, got [db] {$dbValue}");
            }

            return;
        }

        if ($exportValue !== $dbValue) {
            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportValue}, got [db] {$dbValue}");
        }
    }

    protected function checkDateColumn(string $exportColumnName, ?string $exportValue, ?string $dbValue): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        $exportValue = $exportValue ? (new \DateTime($exportValue))->format('Y-m-d') : '<null>';
        $dbValue = $dbValue ? (new \DateTime($dbValue))->format('Y-m-d') : '<null>';

        if ($dbValue != $exportValue) {
            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$exportValue}, got [db] {$dbValue}");
        }
    }

    protected function checkMoneyColumn(string $exportColumnName, mixed $csvValue, mixed $dbValue): void
    {
        $this->columnWatcher->columnSeen($exportColumnName);

        // CSV: number_format($v/100, 2) e.g. "50.00", or empty for null
        // DB: pence as integer/string e.g. "5000", or null
        $csvValue = ($csvValue === null || $csvValue === '') ? null : $csvValue;
        $dbValue = ($dbValue === null) ? null : number_format($dbValue / 100, 2);

        if ($csvValue !== $dbValue) {
            $this->errorReporter->error("Mismatch - expected [export] {$exportColumnName} = {$csvValue}, got [db] {$dbValue}");
        }
    }

    protected function checkExpandedJsonArrayColumn(array $exportRow, string $jsonColumnName, mixed $dbJsonValue, array $choices): void
    {
        // The original JSON array column is still in the CSV (joined with ', ') - mark as seen
        $this->columnWatcher->columnSeen($jsonColumnName);

        $dbArray = $dbJsonValue;
        if (is_string($dbArray)) {
            $dbArray = json_decode($dbArray, true) ?? [];
        }
        if ($dbArray === null) {
            $dbArray = [];
        }

        foreach ($choices as $choice) {
            $choiceForColumn = $choice;
            if ($choiceForColumn === 'other') {
                $choiceForColumn = 'isOther';
            }

            $camelCaseChoice = join('', array_map('ucfirst', explode('-', $choiceForColumn)));
            $expandedColumnName = "{$jsonColumnName} -{$camelCaseChoice}";

            $this->columnWatcher->columnSeen($expandedColumnName);

            // The exporter checks in_array($choice, ...) but uses 'isOther' not 'other',
            // so isOther columns are always 0
            $expectedValue = in_array($choiceForColumn, $dbArray) ? '1' : '0';
            $csvValue = $this->getColumn($exportRow, $expandedColumnName);

            if ($csvValue !== $expectedValue) {
                $this->errorReporter->error("Mismatch - expected [export] {$expandedColumnName} = {$csvValue}, got [db] {$expectedValue}");
            }
        }
    }

    protected function dbToNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return intval($value);
    }

}
