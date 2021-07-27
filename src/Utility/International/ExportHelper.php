<?php

namespace App\Utility\International;

use DateTime;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;

class ExportHelper
{
    protected Bucket $bucket;
    protected string $prefix;
    protected WeekNumberHelper $weekNumberHelper;

    public function __construct(Bucket $exportBucket, WeekNumberHelper $weekNumberHelper)
    {
        $this->bucket = $exportBucket;
        $this->prefix = 'irhs-export/irhs';
        $this->weekNumberHelper = $weekNumberHelper;
    }

    public function getBucket(): Bucket
    {
        return $this->bucket;
    }

    public function upload(string $data, string $week): StorageObject
    {
        return $this->bucket->upload($data, [
            'resumable' => true,
            'name' => $this->getName($week),
        ]);
    }

    public function getWeek(string $name): ?int
    {
        $prefix = preg_quote($this->prefix);
        $pattern = "#^{$prefix}-export-(?P<week>\d+)\.sql$#i";
        return preg_match($pattern, $name, $matches) ? intval($matches['week']) : null;
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
            $week = $this->getWeek($obj->name());
            $exportObject = new ExportObject($obj, $week, $this->weekNumberHelper->getDate($week), $this->weekNumberHelper->getDate($week + 1));
            $exports[$exportObject->getComparator()] = $exportObject;
        }

        krsort($exports);

        return $exports;
    }

    protected function getPossibleWeeks(): array
    {
        $possibilities = [];

        $currentWeek = $this->weekNumberHelper->getWeekNumber(new DateTime());
        $earliestWeek = $currentWeek - 26;

        for($week=$currentWeek-1; $week>=$earliestWeek; $week--) {
            $possibilities['W'.$week] = new ExportObject(null, $week, $this->weekNumberHelper->getDate($week), $this->weekNumberHelper->getDate($week + 1));
        }

        return $possibilities;
    }

    public function getExportsExistingAndPossible(): array
    {
        $existingReports = $this->getExistingExports();
        $possibilities = $this->getPossibleWeeks();

        return array_merge($possibilities, $existingReports);
    }

    public function getStorageObjectIfExists(string $week): ?StorageObject
    {
        $object = $this->bucket->object($this->getName($week));
        return $object->exists() ? $object : null;
    }

    public function getSignedUrl(string $week): ?string
    {
        $object = $this->getStorageObjectIfExists($week);
        return $object ? $object->signedUrl(new DateTime('+30 seconds')) : null;
    }

    protected function getName(string $week): string
    {
        return "{$this->prefix}-export-{$week}.sql";
    }
}