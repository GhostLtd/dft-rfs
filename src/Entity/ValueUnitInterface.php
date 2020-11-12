<?php


namespace App\Entity;


interface ValueUnitInterface
{
    public function getValue() : ?string;
    public function setValue(?string $value);
    public function getUnits() : ?string;
    public function setUnits(?string $units);
}