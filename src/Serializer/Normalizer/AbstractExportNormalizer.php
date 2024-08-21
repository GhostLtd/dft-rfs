<?php

namespace App\Serializer\Normalizer;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractExportNormalizer implements NormalizerInterface
{
    protected SerializerInterface $serializer;

    public function __construct()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, null, null, null, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn($object, $format, $context) => $object::class . ':' . $object->getId(),
        ]);

        $this->serializer = new Serializer([new DateTimeNormalizer(), $normalizer]);
    }

    #[\Override]
    public function normalize($object, $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return array_map(fn(Mapper $mapper) => $mapper->getData($object), $this->getMapping());
    }

    #[\Override]
    abstract public function supportsNormalization($data, $format = null, array $context = []): bool;

    /**
     * @return Mapper[]|array
     */
    abstract protected function getMapping(): array;
}