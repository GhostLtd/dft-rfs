<?php


namespace App\Form\Admin\InternationalSurvey;


use App\Entity\International\Survey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class AddSurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "admin.international.survey";

        $builder
            ->add('company', CompanyType::class, [
                'label' => false,
            ])
            ->add('referenceNumber', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.reference-number.label",
                'help' => "{$translationKeyPrefix}.reference-number.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('surveyPeriodStart', Gds\DateType::class, [
                'label' => "{$translationKeyPrefix}.period-start.label",
                'help' => "{$translationKeyPrefix}.period-start.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('surveyPeriodInDays', Gds\ChoiceType::class, [
                'label' => "{$translationKeyPrefix}.period-days.label",
                'help' => "{$translationKeyPrefix}.period-days.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-radios--inline'],
                'mapped' => false,
                'choices' => [
                    '1 day' => 1,
                    '7 days' => 7,
                    '14 days' => 14,
                    '28 days' => 28,
                ],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "{$translationKeyPrefix}.submit.label",
            ]);

            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
        ]);
    }
}