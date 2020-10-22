<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use App\Form\Gds\DataTransformer\TimeStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeType extends AbstractType
{
    const AM = 'am';
    const PM = 'pm';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subFieldOptions = [
            'error_bubbling' => true,
        ];

        $builder
            ->add('hour', TextType::class, array_merge($subFieldOptions, [
                'label' => 'Hour',
            ]))
            ->add('minute', TextType::class, array_merge($subFieldOptions, [
                'label' => 'Minute',
            ]))
            ->add('am_or_pm', ChoiceType::class, array_merge($subFieldOptions, [
                'label' => 'am or pm',
                'placeholder' => '',
                'choices' => [
                    'am' => self::AM,
                    'pm' => self::PM,
                ],
            ]))
            ->addModelTransformer(new TimeStringTransformer())
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'help' => 'For example, 11:36am or 2:11pm',
            'label_is_page_heading' => false,
            'error_bubbling' => false,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }
}
