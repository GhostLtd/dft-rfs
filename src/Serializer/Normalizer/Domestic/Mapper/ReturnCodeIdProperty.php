<?php

namespace App\Serializer\Normalizer\Domestic\Mapper;

use App\Entity\Domestic\Survey;
use App\Serializer\Normalizer\Mapper\Mapper;

class ReturnCodeIdProperty implements Mapper
{
    #[\Override]
    public function getData($sourceData)
    {
        /** @var Survey $sourceData */

        return $sourceData->getResponse() ? ($sourceData->getResponse()->hasJourneys() ? 21 : null) : null;
    }
}