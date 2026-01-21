<?php

namespace App\Form\Admin;

use App\Entity\EmailBlacklist;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailBlacklistType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('email', Gds\InputType::class, [
                'label' => 'email-blacklist.form.email',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
                'disabled' => $isEdit,
            ])
            ->add('reason', Gds\TextareaType::class, [
                'label' => 'email-blacklist.form.reason',
                'label_attr' => ['class' => 'govuk-label--s'],
                'required' => false,
            ])
            ->add('buttons', Gds\ButtonGroupType::class);

        $builder->get('buttons')
            ->add('submit', Gds\ButtonType::class, [
                'label' => $isEdit ? 'email-blacklist.form.save' : 'email-blacklist.form.submit',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'common.actions.cancel',
                'translation_domain' => 'messages',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmailBlacklist::class,
            'translation_domain' => 'admin',
            'is_edit' => false,
        ]);
    }
}
