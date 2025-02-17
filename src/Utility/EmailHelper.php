<?php

namespace App\Utility;

class EmailHelper
{
    public static function convertStringListToArray(?string $list): array
    {
        if (!$list) {
            return [];
        }
        $emails = explode(',', $list);
        return array_values(self::cleanEmailArray($emails));
    }

    protected static function cleanEmailArray(array $emails): array
    {
        $emails = array_filter($emails, fn(?string $email) => $email !== null);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn(string $email) => $email !== '');
        return array_unique($emails);
    }

    public static function convertArrayListToString(?array $list): ?string
    {
        if (!$list) {
            return null;
        }
        return implode(',', self::cleanEmailArray($list));
    }

    public static function mergeStringLists(?string $list1, ...$otherLists): ?string
    {
        $emails = self::convertStringListToArray($list1);
        foreach ($otherLists as $list) {
            $emails = array_merge($emails, self::convertStringListToArray($list));
        }
        return self::convertArrayListToString($emails);
    }
}