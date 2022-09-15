<?php

namespace App\Serializer;

use App\Serializer\Encoder\SqlServerInsertEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SqlServerBooleanNormalizer implements NormalizerInterface
{
    public function supportsNormalization($data, $format = null): bool
    {
        return is_bool($data) && $format === SqlServerInsertEncoder::FORMAT;
    }

    public function normalize($object, $format = null, array $context = []): ?int
    {
        return $object === true
            ? 1
            : ($object === false
                ? 0
                : null);
    }
}