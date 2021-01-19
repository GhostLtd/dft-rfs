<?php

namespace App\Form\Admin\DomesticSurvey\Edit;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;

class InitialDetailsType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dateValidation = function(string $group) {
            return new NotNull([
                'message' => "common.date.not-null",
                'groups' => [$group],
            ]);
        };

//        $validValidation = function(string $group) {
//            return new Valid(['groups' => [$group]]);
//        };

        $builder
            ->setDataMapper($this)
            ->add('contact_details', ContactDetailsType::class, [
                'inherit_data' => true,
                'label_attr' => ['class' => 'govuk-label--m']
            ])
            ->add('isInPossessionOfVehicle', Gds\ChoiceType::class, [
                'choices' => SurveyResponse::IN_POSSESSION_CHOICES,
                'label' => "domestic.survey-response.in-possession-of-vehicle.is-in-possession-of-vehicle.label",
                'choice_options' => [
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_ON_HIRE => ['conditional_form_name' => 'hiree_details'],
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN => ['conditional_form_name' => 'scrapped_details'],
                    SurveyResponse::IN_POSSESSION_TRANSLATION_PREFIX . SurveyResponse::IN_POSSESSION_SOLD => ['conditional_form_name' => 'sold_details'],
                ],
            ])
            ->add('hiree_details', HireeDetailsType::class, [
                'label' => false,
                'inherit_data' => true,
            ])
            ->add('sold_details', SoldDetailsType::class, [
                'label' => false,
                'inherit_data' => true,
                'date_mapped' => false,
                'date_property_path' => null,
                'date_constraints' => [$dateValidation('admin_sold')],
//                'constraints' => [$validValidation('admin_sold')],
            ])
            ->add('scrapped_details', ScrappedDetailsType::class, [
                'label' => false,
                'is_child_form' => true,
                'inherit_data' => true,
                'date_mapped' => false,
                'date_property_path' => null,
                'date_constraints' => [$dateValidation('admin_scrapped')],
//                'constraints' => [$validValidation('admin_scrapped')],
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
                $isInPossession = $form->get('isInPossessionOfVehicle')->getData();
                $validationGroups = ['admin_correspondence'];

                switch ($isInPossession) {
                    case SurveyResponse::IN_POSSESSION_ON_HIRE:
                        $validationGroups[] = 'admin_on_hire';
                        break;
                    case SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN:
                        $validationGroups[] = 'admin_scrapped';
                        break;
                    case SurveyResponse::IN_POSSESSION_SOLD:
                        $validationGroups[] = 'admin_sold';
                        break;
                }

                dump($validationGroups);
                return $validationGroups;
            }
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if (null === $viewData) {
            return;
        }

        if (!$viewData instanceof SurveyResponse) {
            throw new Exception\UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);
        $accessor = PropertyAccess::createPropertyAccessor();

        foreach(['contactName', 'contactTelephone', 'contactEmail', 'isInPossessionOfVehicle'] as $field) {
            $forms[$field]->setData($accessor->getValue($viewData, $field));
        }

        switch ($viewData->getIsInPossessionOfVehicle()) {
            case SurveyResponse::IN_POSSESSION_ON_HIRE:
                foreach(['hireeAddress', 'hireeName', 'hireeEmail'] as $field) {
                    $forms[$field]->setData($accessor->getValue($viewData, $field));
                }
                break;
            case SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN:
                $forms['scrappedDate']->setData($viewData->getUnableToCompleteDate());
                break;
            case SurveyResponse::IN_POSSESSION_SOLD:
                $forms['soldDate']->setData($viewData->getUnableToCompleteDate());
                foreach(['newOwnerAddress', 'newOwnerName', 'newOwnerEmail'] as $field) {
                    $forms[$field]->setData($accessor->getValue($viewData, $field));
                }
                break;
        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (!$viewData instanceof SurveyResponse) {
            throw new Exception\UnexpectedTypeException($viewData, SurveyResponse::class);
        }

        $accessor = PropertyAccess::createPropertyAccessor();
        $isInPossession = $forms['isInPossessionOfVehicle']->getData();

        foreach(['contactName', 'contactTelephone', 'contactEmail', 'isInPossessionOfVehicle'] as $field) {
            $accessor->setValue($viewData, $field, $forms[$field]->getData());
        }

        $getFieldsExcluding = function(string $excludeGroup) {
            $fields = [];

            if ($excludeGroup !== 'hire') {
                $fields = array_merge($fields, ['hireeAddress', 'hireeName', 'hireeEmail']);
            }

            if ($excludeGroup !== 'scrapped') {
                $fields = array_merge($fields, []);
            }

            if ($excludeGroup !== 'sold') {
                $fields = array_merge($fields, ['newOwnerAddress', 'newOwnerName', 'newOwnerEmail']);
            }

            return $fields;
        };

        switch ($isInPossession) {
            case SurveyResponse::IN_POSSESSION_ON_HIRE:
                $viewData->setUnableToCompleteDate(null);
                foreach(['hireeAddress', 'hireeName', 'hireeEmail'] as $field) {
                    $accessor->setValue($viewData, $field, $forms[$field]->getData());
                }

                foreach($getFieldsExcluding('hire') as $field) {
                    $accessor->setValue($viewData, $field, null);
                }
                break;
            case SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN:
                $viewData->setUnableToCompleteDate($forms['scrappedDate']->getData());

                foreach($getFieldsExcluding('scrapped') as $field) {
                    $accessor->setValue($viewData, $field, null);
                }
                break;
            case SurveyResponse::IN_POSSESSION_SOLD:
                $viewData->setUnableToCompleteDate($forms['soldDate']->getData());
                foreach(['newOwnerAddress', 'newOwnerName', 'newOwnerEmail'] as $field) {
                    $accessor->setValue($viewData, $field, $forms[$field]->getData());
                }

                foreach($getFieldsExcluding('sold') as $field) {
                    $accessor->setValue($viewData, $field, null);
                }
                break;
        }
    }
}