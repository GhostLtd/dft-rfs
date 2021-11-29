<?php

namespace App\Utility;

use DateTime;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;

abstract class AbstractExportHelper
{
    protected Bucket $bucket;
    protected string $prefix;

    abstract public function getPrefix(): string;
    abstract public function getReportQuarterHelperClass(): string;

    public function __construct(Bucket $exportBucket)
    {
        $this->bucket = $exportBucket;
        $this->prefix = $this->getPrefix();
    }

    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    public function upload($data, string $year, string $quarter): StorageObject
    {
        return $this->bucket->upload($data, [
            'resumable' => true,
            'name' => $this->getName($year, $quarter),
        ]);
    }

    public function getYearAndQuarter(string $name): array
    {
        $prefix = preg_quote($this->prefix);
        $pattern = "#^{$prefix}-export-(?P<year>\d{4})-Q(?P<quarter>[1-4]{1})\.sql$#i";

        return preg_match($pattern, $name, $matches) ?
            [intval($matches['year']), intval($matches['quarter'])] :
            [null, null];
    }

    /**
     * @return ExportObject[]
     */
    public function getExistingExports(): array
    {
        $iterator = $this->bucket->objects([
            'prefix' => "{$this->prefix}-export-",
        ]);

        $exports = [];
        foreach(iterator_to_array($iterator) as $obj) {
            [$year, $quarter] = $this->getYearAndQuarter($obj->name());

            if ($year === null || $quarter === null) {
                continue; // Non-matching file in folder...
            }

            $exportObject = new ExportObject($obj, $year, $quarter);
            $exports[$exportObject->getComparator()] = $exportObject;
        }

        krsort($exports);

        return $exports;
    }

    protected function getPossibleYearsAndQuarters(): array
    {
        $possibilities = [];

        $reportQuarterHelperClass = $this->getReportQuarterHelperClass();
        assert(is_a($reportQuarterHelperClass, ReportQuarterHelperInterface::class, true));

        [$currentQuarter, $currentYear] = $reportQuarterHelperClass::getQuarterAndYear(new DateTime());
        $lastYear = $currentYear - 1;

        for($quarter=($currentQuarter - 1); $quarter>=1; $quarter--) {
            $possibilities[$currentYear.'-'.$quarter] = new ExportObject(null, $currentYear, $quarter);
        }

        for($quarter=4; $quarter>=1; $quarter--) {
            $possibilities[$lastYear.'-'.$quarter] = new ExportObject(null, $lastYear, $quarter);
        }

        return $possibilities;
    }

    public function getExportsExistingAndPossible(): array
    {
        $existingReports = $this->getExistingExports();
        $possibilities = $this->getPossibleYearsAndQuarters();

        return array_merge($possibilities, $existingReports);
    }

    public function getStorageObjectIfExists(string $year, string $quarter): ?StorageObject
    {
        $object = $this->bucket->object($this->getName($year, $quarter));
        return $object->exists() ? $object : null;
    }

    public function getSignedUrl(string $year, string $quarter): ?string
    {
        $object = $this->getStorageObjectIfExists($year, $quarter);
        return $object ? $object->signedUrl(new DateTime('+30 seconds')) : null;
    }

    protected function getName(string $year, string $quarter): string
    {
        return "{$this->prefix}-export-{$year}-Q{$quarter}.sql";
    }
}