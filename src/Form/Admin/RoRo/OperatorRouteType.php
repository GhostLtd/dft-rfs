<?php

namespace App\Form\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\Route\Route;
use App\Repository\Route\RouteRepository;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;

class OperatorRouteType extends AbstractType
{
    const ROUTE_BACKDATE_SURVEYS = 'route_backdate_surveys';

    public function __construct(protected RouteRepository $routeRepository)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Operator $operator */
        $operator = $options['operator'];
        $routeIdsToExclude = $operator->getRoutes()->map(fn(Route $route) => $route->getId())->toArray();

        $routeChoices = $this->routeRepository
            ->createQueryBuilder('route')
            ->select('route, uk_port, foreign_port')
            ->join('route.ukPort', 'uk_port')
            ->join('route.foreignPort', 'foreign_port')
            ->where('route.isActive = 1')
            ->getQuery()
            ->getResult();

        $builder
            ->add('route', ChoiceType::class, [
                'placeholder' => '-',
                'choices' => $routeChoices,
                'choice_attr' => function(Route $route) use ($routeIdsToExclude) {
                    return in_array($route->getId(), $routeIdsToExclude) ?
                        ['disabled' => 'disabled'] :
                        [];
                },
                'choice_label' => function(Route $route) use ($routeIdsToExclude) {
                    $label = $route->getUkPort()->getName().' / '.$route->getForeignPort()->getName();
                    $label .= in_array($route->getId(), $routeIdsToExclude) ? ' (already assigned)' : '';

                    return $label;
                },
                'constraints' => [
                    new NotBlank([], "common.choice.not-null"),
                ],
                'expanded' => false,
                'attr' => ['class' => 'govuk-input--width-10'],
            ]);

        $builder
            ->add('backdateSurveys', BooleanChoiceType::class, [
                'choice_options' => [
                    'common.choices.boolean.yes' => [
                        'conditional_form_name' => 'backdateSurveysGroup',
                    ]
                ],
                'constraints' => [new NotNull],
                'mapped' => false,
            ])
            ->add('backdateSurveysGroup', FieldsetType::class, [
                'label' => 'Start surveys on year / month',
            ]);

        $currentYear = intval((new \DateTime())->format('Y'));
        $lastYear = $currentYear - 1;

        $backdateSurveysGroup = $builder->get('backdateSurveysGroup');
        $backdateSurveysGroup
            ->add('year', NumberType::class, [
                'attr' => ['class' => 'govuk-input--width-5'],
                'label' => 'Year',
                'mapped' => false,
                'constraints' => [
                    new NotNull(['groups' => self::ROUTE_BACKDATE_SURVEYS]),
                    new Range(['min' => $lastYear, 'max' => $currentYear]),
                ],
            ])
            ->add('month', NumberType::class, [
                'attr' => ['class' => 'govuk-input--width-5'],
                'label' => 'Month',
                'mapped' => false,
                'constraints' => [
                    new NotNull(['groups' => self::ROUTE_BACKDATE_SURVEYS]),
                    new Range(['min' => 1, 'max' => 12]),
                ],
            ]);

        $builder
            ->add('submit', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Save',
            ])
            ->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['operator']);
        $resolver->setAllowedTypes('operator', Operator::class);

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            // If backdateSurveys is ticked, validate the year/month...
            $backdateSurveys = $form->get('backdateSurveys')->getData();
            return $backdateSurveys ? ['Default', self::ROUTE_BACKDATE_SURVEYS] : ['Default'];
        });
    }
}