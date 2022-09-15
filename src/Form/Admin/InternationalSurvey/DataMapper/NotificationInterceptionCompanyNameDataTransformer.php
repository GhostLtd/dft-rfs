<?php

namespace App\Form\Admin\InternationalSurvey\DataMapper;

use App\Entity\International\NotificationInterceptionCompanyName;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

class NotificationInterceptionCompanyNameDataTransformer implements DataTransformerInterface
{
    private ?Collection $transformValue;

    /**
     * @param ?Collection $value
     */
    public function transform($value): ?string
    {
        $this->transformValue = $value;
        return $value
            ? implode("\r\n", array_map(fn(NotificationInterceptionCompanyName $n) => $n->getName(), $value->toArray()))
            : null;
    }

    public function reverseTransform($value)
    {
        $data = explode("\r\n", $value);
        $data = array_map(fn($d) => trim($d), $data);
        $data = array_filter($data, fn($d) => !empty($d));
        return array_map(fn($d) => $this->getTransformValueOrNewCompanyName($d), array_values($data));
    }

    protected function getTransformValueOrNewCompanyName(string $name): NotificationInterceptionCompanyName
    {
        /** @var NotificationInterceptionCompanyName $cn */
        foreach ($this->transformValue ?? [] as $cn) {
            if ($cn->getName() === $name) {
                return $cn;
            }
        }
        return (new NotificationInterceptionCompanyName())->setName($name);
    }
}