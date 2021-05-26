<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\PasscodeUser;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResetPasscodeConfirmAction extends AbstractConfirmAction
{
    /** @var PasscodeUser */
    protected $subject;
    protected PasscodeGenerator $passcodeGenerator;
    protected EntityManagerInterface $entityManager;
    protected ?string $passcode;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, PasscodeGenerator $passcodeGenerator, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->entityManager = $entityManager;
        $this->passcodeGenerator = $passcodeGenerator;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'admin.passcode-reset';
    }

    public function doConfirmedAction()
    {
        $this->passcode = $this->passcodeGenerator->generatePasscode();
        $this->subject->setPlainPassword($this->passcode);
        $this->entityManager->flush();
    }

    public function getPasscode(): ?string
    {
        return $this->passcode;
    }
}