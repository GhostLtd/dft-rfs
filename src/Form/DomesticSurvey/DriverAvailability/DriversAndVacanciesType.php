<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\DriverAvailability;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DriversAndVacanciesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.driver-availability.drivers";

        $builder
            ->setDataMapper(new DriversAndVacanciesTypeMapper())

            ->add('numberOfDriversEmployed', Gds\NumberType::class, [
                'property_path' => 'survey.driverAvailability.numberOfDriversEmployed',
                'label' => "{$translationKeyPrefix}.number-of-drivers-employed.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-drivers-employed.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('hasVacancies', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.hasVacancies',
                'label' => "{$translationKeyPrefix}.has-vacancies.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.has-vacancies.help",
                'choices' => DriverAvailability::TRISTATE_CHOICES,
                'choice_options' => [
                    'common.choices.boolean.yes' => ['conditional_form_name' => 'vacancy_details'],
                ],
            ])
            ->add('vacancy_details', FormType::class, [
                'inherit_data' => true,
                'label' => false,
            ])
            ->add('numberOfDriversThatHaveLeft', Gds\NumberType::class, [
                'property_path' => 'survey.driverAvailability.numberOfDriversThatHaveLeft',
                'label' => "{$translationKeyPrefix}.number-of-drivers-that-have-left.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-drivers-that-have-left.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
        ;

        $builder->get('vacancy_details')
            ->add('numberOfDriverVacancies', Gds\NumberType::class, [
                'property_path' => 'survey.driverAvailability.numberOfDriverVacancies',
                'label' => "{$translationKeyPrefix}.number-of-driver-vacancies.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-driver-vacancies.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('reasonsForDriverVacancies', Gds\ChoiceType::class, [
                'multiple' => true,
                'property_path' => 'survey.driverAvailability.reasonsForDriverVacancies',
                'label' => "{$translationKeyPrefix}.reasons-for-vacancies.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-vacancies.help",
                'choices' => DriverAvailability::VACANCY_REASON_CHOICES,
                'choice_options' => [
                    DriverAvailability::VACANCY_REASON_TRANSLATION_PREFIX . 'other' => ['conditional_form_name' => 'reasonsForDriverVacanciesOther'],
                ],
            ])
            ->add('reasonsForDriverVacanciesOther', Gds\InputType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForDriverVacanciesOther',
                'label' => "{$translationKeyPrefix}.reasons-for-vacancies-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-vacancies-other.help",
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['driver-availability.drivers-and-vacancies']
        ]);
    }
}