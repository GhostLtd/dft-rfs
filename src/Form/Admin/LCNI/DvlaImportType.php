<?php

declare(strict_types=1);

namespace App\Form\Admin\LCNI;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\NotBlank;

class DvlaImportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('upload', Gds\FileUploadType::class, [
                'label' => false,
                'constraints' => [new NotBlank()],
            ]);
        $builder->add('buttons', Gds\ButtonGroupType::class, []);

        $builder->get('buttons')
            ->add('buttons', Gds\ButtonGroupType::class)
            ->get('buttons')
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => "notification-interception.dvla-import.form.submit",
            ])
            ->add('cancel', Gds\LinkType::class, [
                'label' => "notification-interception.form.cancel",
                'href' => $options['cancel_url'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['cancel_url']);
        $resolver->setDefaults([
            'help' => 'notification-interception.dvla-import.form.help',
            'translation_domain' => 'admin',
        ]);
    }
}
