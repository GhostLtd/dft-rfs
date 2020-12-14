<?php


namespace App\Form\Extension;


use App\EventSubscriber\FormValidationLoggerSubscriber;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

class FormTypeValidationLoggerExtension extends AbstractTypeExtension
{
    /**
     * @var FormValidationLoggerSubscriber
     */
    private $eventSubscriber;

    public function __construct(FormValidationLoggerSubscriber $eventSubscriber)
    {
        $this->eventSubscriber = $eventSubscriber;
    }

    public static function getExtendedTypes()
    {
        return [
            FormType::class
        ];
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->eventSubscriber);
    }
}