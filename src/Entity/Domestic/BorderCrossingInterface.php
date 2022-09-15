<?php

namespace App\Entity\Domestic;

interface BorderCrossingInterface
{
    public function getBorderCrossed(): ?bool;
    public function setBorderCrossed(?bool $borderCrossed): self;
    public function getBorderCrossingLocation(): ?string;
    public function setBorderCrossingLocation(?string $borderCrossingLocation): self;
}