<?php

namespace App\Utility\Google;

use DateTime;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageObject;

class InternationalExportHelper
{
    protected Bucket $bucket;
    protected string $prefix;
    protected DateTime $weekOne;

    public function __construct(Bucket $exportBucket)
    {
        $this->bucket = $exportBucket;
        $this->prefix = 'irhs';
        $this->weekOne = new DateTime('1991-09-23');
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
        $pattern = "/^{$this->prefix}-export-(?P<week>\d+)\.sql$/i";
        return preg_match($pattern, $name, $matches) ? intval($matches['week']) : null;
    }

    /**
     * @return InternationalExportObject[]
     */
    public function getExistingExports(): array
    {
        $iterator = $this->bucket->objects([
            'prefix' => "{$this->prefix}-export-",
        ]);

        $exports = [];
        foreach(iterator_to_array($iterator) as $obj) {
            $week = $this->getWeek($obj->name());
            $exportObject = new InternationalExportObject($obj, $week);
            $exports[$exportObject->getComparator()] = $exportObject;
        }

        krsort($exports);

        return $exports;
    }

    protected function determineWeek(DateTime $date)
    {
        return intval(floor($date->diff($this->weekOne)->days / 7));
    }

    protected function getPossibleWeeks(): array
    {
        $possibilities = [];

        $currentWeek = $this->determineWeek(new DateTime());
        $earliestWeek = $currentWeek - 26;

        for($week=$currentWeek-1; $week>=$earliestWeek; $week--) {
            $possibilities['W'.$week] = new InternationalExportObject(null, $week);
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
        return $object ? $object->signedUrl(new DateTime('+1 hour')) : null;
    }

    protected function getName(string $week): string
    {
        return "{$this->prefix}-export-{$week}.sql";
    }
}