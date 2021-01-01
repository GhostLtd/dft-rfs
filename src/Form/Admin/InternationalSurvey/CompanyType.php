<?php


namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\Company;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "admin.international.company";

        $builder
            ->add('businessName', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.business-name.label",
                'help' => "{$translationKeyPrefix}.business-name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}