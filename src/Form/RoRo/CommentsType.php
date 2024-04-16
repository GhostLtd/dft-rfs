<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comments', TextareaType::class, [
                'label_is_page_heading' => true,
                'label' => 'roro.survey.comments.comments.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => 'roro.survey.comments.comments.help',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
        ]);
    }
}
