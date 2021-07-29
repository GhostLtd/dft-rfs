<?php


namespace Ghost\GovUkFrontendBundle\Form\Type;


use Ghost\GovUkFrontendBundle\Model\Time;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType as ExtendedDateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class DateTimeType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'gds_datetime';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, [
                'label' => false,
            ])
            ->add('time', TimeType::class, [
                'label' => false,
                'help' => null,
            ])
            ->addModelTransformer(new CallbackTransformer(
                function($data) {
                    return [
                        'date' => $data,
                        'time' => $data ? Time::fromDateTime($data) : null,
                    ];
                }, function($data) {
                    /** @var \DateTime[] $data */
                    if (is_null($data['time']) && is_null($data['date'])) {
                        return null;
                    }

                    if (is_null($data['time']) && $data['date'] instanceof \DateTimeInterface) {
                        throw new Exception\TransformationFailedException('Invalid time value', 0, null, 'Provide a valid time');
                    }
                    if (is_null($data['date']) && $data['time'] instanceof \DateTimeInterface) {
                        throw new Exception\TransformationFailedException('Invalid date value', 0, null, 'Provide a valid date');
                    }

                    $merged = clone $data['date'];
                    $merged->setTime($data['time']->format('H'), $data['time']->format('i'), $data['time']->format('s'));
                    return $merged;
                }
            ))
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_attr' => ['class' => 'govuk-label--s'],
            'error_bubbling' => false,
        ]);
    }
}