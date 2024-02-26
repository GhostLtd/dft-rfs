<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\PasscodeUser;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasscodeConfirmAction extends AbstractConfirmAction
{
    /** @var PasscodeUser */
    protected $subject;
    protected ?string $passcode = null;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, protected PasscodeGenerator $passcodeGenerator, protected EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.passcode-reset';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->subject->setPassword(null);
        $this->entityManager->flush();
    }
}