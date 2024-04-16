<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Form\LongAddressType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CorrespondenceAddressType extends AbstractType implements DataMapperInterface
{
    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => 'yes',
        self::CHOICE_NO => 'no',
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationPrefix = "pre-enquiry.correspondence-address";

        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) use ($translationPrefix) {
                $form = $formEvent->getForm();
                $data = $formEvent->getData();

                assert($data instanceof PreEnquiryResponse);

                $correctAddressPrefix = "{$translationPrefix}.correct-address";

                if ($data->getPreEnquiry()->hasInvitationAddress()) {
                    $form
                        ->add('isCorrectAddress', Gds\ChoiceType::class, [
                            'label' => "{$correctAddressPrefix}.label",
                            'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                            'help' => "{$correctAddressPrefix}.help",
                            'choices' => self::CHOICES,
                            'choice_options' => [
                                self::CHOICE_NO => [
                                    'conditional_form_name' => 'contactAddress',
                                ],
                            ],
                            'validation_groups' => ['Default'],
                        ]);
                }

                $form
                    ->add('contactAddress', LongAddressType::class, [
                        'label' => "{$translationPrefix}.address.label",
                        'label_attr' => [
                            'class' => 'govuk-label--s',
                        ],
                        'help' => "{$translationPrefix}.address.help",
                        'include_addressee' => false,
                    ]);
            })
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => function (Form $form) {
                $hasInvitationAddress = $form->has('isCorrectAddress');
                $alreadyHaveCorrectAddress = $hasInvitationAddress &&
                    $form->get('isCorrectAddress')->getData() === self::CHOICES[self::CHOICE_YES];

                return $alreadyHaveCorrectAddress ?
                    ["is_correct_address"] :
                    ['correspondence_address'];
            },
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        // there is no data yet, so nothing to pre-populate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof PreEnquiryResponse) {
            throw new UnexpectedTypeException($viewData, PreEnquiryResponse::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['contactAddress'])) {
            return;
        }

        if (isset($forms['isCorrectAddress'])) {
            $isCorrectAddress = $viewData->getIsCorrectAddress();
            if ($isCorrectAddress !== null) {
                $forms['isCorrectAddress']->setData(self::CHOICES[$isCorrectAddress ? self::CHOICE_YES : self::CHOICE_NO]);
            }
        }

        $forms['contactAddress']->setData($viewData->getIsCorrectAddress() ? null : $viewData->getContactAddress());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        assert($viewData instanceof PreEnquiryResponse);

        if (!isset($forms['contactAddress'])) return;

        if (!isset($forms['isCorrectAddress'])) {
            $isCorrectAddress = false;
        } else {
            $isCorrectAddress = !empty($forms['isCorrectAddress']->getData())
                ? $forms['isCorrectAddress']->getData() === self::CHOICES[self::CHOICE_YES]
                : null;
        }

        $address = $isCorrectAddress ?
            (clone $viewData->getPreEnquiry()->getInvitationAddress()) :
            $forms['contactAddress']->getData();

        $viewData
            ->setContactAddress($address)
            ->setIsCorrectAddress($isCorrectAddress);

        $viewData->getContactAddress()->setLine1($viewData->getCompanyName());
    }
}
