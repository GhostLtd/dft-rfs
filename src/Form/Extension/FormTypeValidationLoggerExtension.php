<?php

namespace App\Form\Extension;

use App\EventSubscriber\FormValidationLoggerSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class FormTypeValidationLoggerExtension extends AbstractTypeExtension
{
    public function __construct(protected FormValidationLoggerSubscriber $eventSubscriber)
    {
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [
            FormType::class
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this->eventSubscriber);
    }
}
