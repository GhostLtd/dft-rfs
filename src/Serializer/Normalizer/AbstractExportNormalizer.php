<?php

namespace App\Serializer\Normalizer;

use App\Serializer\Normalizer\Mapper\Mapper;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractExportNormalizer implements ContextAwareNormalizerInterface
{
    protected SerializerInterface $serializer;

    public function __construct()
    {
        $normalizer = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter(), null, null, null, null, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                return get_class($object) . ':' . $object->getId();
            },
        ]);

        $this->serializer = new Serializer([new DateTimeNormalizer(), $normalizer]);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        return array_map(fn(Mapper $mapper) => $mapper->getData($object), $this->getMapping());
    }

    abstract public function supportsNormalization($data, $format = null, array $context = []);

    /**
     * @return Mapper[]|array
     */
    abstract protected function getMapping(): array;
}