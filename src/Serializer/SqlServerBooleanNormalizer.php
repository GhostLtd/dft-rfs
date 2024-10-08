<?php

namespace App\Serializer;

use App\Serializer\Encoder\SqlServerInsertEncoder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SqlServerBooleanNormalizer implements NormalizerInterface
{
    #[\Override]
    public function supportsNormalization($data, $format = null, array $context = []): bool
    {
        return is_bool($data) && $format === SqlServerInsertEncoder::FORMAT;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'native-boolean' => $format === SqlServerInsertEncoder::FORMAT,
        ];
    }

    #[\Override]
    public function normalize($object, $format = null, array $context = []): ?int
    {
        return $object === true
            ? 1
            : ($object === false
                ? 0
                : null);
    }
}