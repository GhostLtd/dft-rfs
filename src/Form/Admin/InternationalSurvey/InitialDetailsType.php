<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Form\Admin\InternationalSurvey\DataMapper\BusinessDetailsDataMapper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class InitialDetailsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper($this)
            ->add('correspondence', CorrespondenceDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'include_buttons' => false,
                'validation_groups' => $options['validation_groups'],
            ])
            ->add('business', BusinessDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'include_buttons' => false,
                'validation_groups' => $options['validation_groups'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => function(FormInterface $form) {
                /** @var SurveyResponse $data */
                $data = $form->getData();

                $validationGroups = ['admin_correspondence'];

                if ($data && $data->getActivityStatus() === SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) {
                    $validationGroups[] = 'admin_business_details';
                }

                return $validationGroups;
            },
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        (new BusinessDetailsDataMapper())->mapDataToForms($viewData, $forms);

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach(['contactName', 'contactEmail', 'contactTelephone'] as $field) {
            $forms[$field]->setData($accessor->getValue($viewData, $field));
        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        if (!$viewData instanceof SurveyResponse) {
            throw new UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        (new BusinessDetailsDataMapper())->mapFormsToData($forms, $viewData);

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach(['contactName', 'contactEmail', 'contactTelephone'] as $field) {
            $accessor->setValue($viewData, $field, $forms[$field]->getData());
        }
    }
}