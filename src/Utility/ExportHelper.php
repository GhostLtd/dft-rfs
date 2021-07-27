<?php


namespace App\Utility;


class ExportHelper
{
    public function expandConstBitMap($field, $map, $addCommaToLastItem = false, $true = -1, $false = 0) {
        $sql = "";

        $lastKey = array_key_last($map);
        foreach ($map as $exportName => $constValue) {
            $sql .= ($constValue === null
                ? "(CASE WHEN {$field} IS NULL THEN {$true} ELSE {$false} END) as {$exportName}"
                : "(CASE WHEN {$field} = '{$constValue}' THEN {$true} ELSE {$false} END) as {$exportName}");
            if ($addCommaToLastItem || ($lastKey !== $exportName)) {
                $sql .= ", ";
            }
        }
        return $sql;
    }
}