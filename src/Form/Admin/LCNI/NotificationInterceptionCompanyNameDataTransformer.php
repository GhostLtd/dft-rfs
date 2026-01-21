<?php

namespace App\Form\Admin\LCNI;

use App\Entity\NotificationInterceptionCompanyName;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

class NotificationInterceptionCompanyNameDataTransformer implements DataTransformerInterface
{
    private ?Collection $transformValue = null;

    #[\Override]
    public function transform($value): ?string
    {
        $this->transformValue = $value;
        return $value
            ? implode("\r\n", array_map(fn(NotificationInterceptionCompanyName $n) => $n->getName(), $value->toArray()))
            : null;
    }

    #[\Override]
    public function reverseTransform($value): array
    {
        $data = explode("\r\n", $value);
        $data = array_map(fn($d) => mb_trim($d), $data);
        $data = array_filter($data, fn($d) => !empty($d));
        return array_map(fn($d) => $this->getTransformValueOrNewCompanyName($d), array_values($data));
    }

    protected function getTransformValueOrNewCompanyName(string $name): NotificationInterceptionCompanyName
    {
        /** @var NotificationInterceptionCompanyName $cn */
        foreach ($this->transformValue ?? [] as $cn) {
            if (mb_trim($cn->getName()) === $name) {
                // Update the name to ensure any whitespace is trimmed
                $cn->setName($name);
                return $cn;
            }
        }
        return (new NotificationInterceptionCompanyName())->setName($name);
    }
}