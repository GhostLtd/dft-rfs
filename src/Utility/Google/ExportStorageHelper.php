<?php

namespace App\Utility\Google;

use DateTime;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;

class ExportStorageHelper
{
    protected Bucket $bucket;
    protected string $prefix;

    public function __construct(Bucket $exportBucket)
    {
        $this->bucket = $exportBucket;
        $this->prefix = 'csrgt';
    }

    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    public function upload(string $data, string $year, string $quarter): StorageObject
    {
        return $this->bucket->upload($data, [
            'resumable' => true,
            'name' => $this->getName($year, $quarter),
        ]);
    }

    public function getYearAndQuarter(string $name): array
    {
        $pattern = "/^{$this->prefix}-export-(?P<year>\d{4})-Q(?P<quarter>[1-4]{1})\.sql$/i";

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
            $exportObject = new ExportObject($obj, $year, $quarter);
            $exports[$exportObject->getComparator()] = $exportObject;
        }

        krsort($exports);

        return $exports;
    }

    protected function determineQuarter(DateTime $date)
    {
        $month = intval($date->format('n'));

        if ($month <= 3) {
            return 1;
        } else if ($month <= 6) {
            return 2;
        } else if ($month <= 9) {
            return 3;
        } else {
            return 4;
        }
    }

    protected function getPossibleYearsAndQuarters(): array
    {
        $possibilities = [];

        $currentDate = new Datetime();
        $currentQuarter = $this->determineQuarter(new DateTime());
        $currentYear = intval($currentDate->format('Y'));
        $lastYear = $currentYear - 1;

        for($quarter=$currentQuarter; $quarter>=1; $quarter--) {
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
        return $object ? $object->signedUrl(new DateTime('+1 hour')) : null;
    }

    protected function getName(string $year, string $quarter): string
    {
        return "{$this->prefix}-export-{$year}-Q{$quarter}.sql";
    }
}