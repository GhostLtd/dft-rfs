<?php

namespace App\Form\Admin;

use App\Entity\Route\Route;
use App\Repository\Route\ForeignPortRepository;
use App\Repository\Route\UkPortRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RouteType extends AbstractType
{
    public function __construct(protected ForeignPortRepository $foreignPortRepository, protected UkPortRepository $ukPortRepository)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $getChoices = fn(ServiceEntityRepository $r) => $r->createQueryBuilder('p')
            ->orderBy('p.name')
            ->getQuery()
            ->getResult();

        $builder
            ->add('ports', FieldsetType::class, [
                'label' => false,
            ])
            ->add('isActive', BooleanChoiceType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('submit', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Save',
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);

        $builder
            ->get('ports')
            ->add('ukPort', ChoiceType::class, [
                'placeholder' => '-',
                'choices' => $getChoices($this->ukPortRepository),
                'choice_label' => 'name',
                'expanded' => false,
                'disabled' => !$options['add'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('foreignPort', ChoiceType::class, [
                'placeholder' => '-',
                'choices' => $getChoices($this->foreignPortRepository),
                'choice_label' => 'name',
                'expanded' => false,
                'disabled' => !$options['add'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'add' => false,
            'data_class' => Route::class,
            'error_mapping' => [
                'ports' => 'ports', // Enables red line alongside ports group when an error targets it
            ],
        ]);

        $resolver->setAllowedTypes('add', 'bool');

        $resolver->setDefault('validation_groups', function(Options $options) {
            $groups = $options['add'] ? 'admin_add_route' : 'admin_edit_route';
            return [$groups];
        });
    }
}
