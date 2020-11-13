<?php


namespace App\Entity;


interface ValueUnitInterface
{
    public function getValue() : ?string;
    public function setValue(?string $value);
    public function getUnit() : ?string;
    public function setUnit(?string $unit);
}