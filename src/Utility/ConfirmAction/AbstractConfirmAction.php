<?php

namespace App\Utility\ConfirmAction;

use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractConfirmAction implements ConfirmActionInterface
{
    protected $subject;
    protected FormFactoryInterface $formFactory;
    protected FlashBagInterface $flashBag;
    protected TranslatorInterface $translator;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getConfirmedBanner(): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.success'),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.confirmed-notification.heading"),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.confirmed-notification.content"),
            ['style' => NotificationBanner::STYLE_SUCCESS]
        );
    }

    public function getCancelledBanner(): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.important'),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.cancelled-notification.heading"),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.cancelled-notification.content"),
        );
    }

    protected function domainTrans($key)
    {
        return $this->translator->trans($key, $this->getTranslationParameters(), $this->getTranslationDomain());
    }

    public function getFormOptions(): array
    {
        return [
            'translation_domain' => $this->getTranslationDomain(),
            'translation_key_prefix' => $this->getTranslationKeyPrefix(),
            'label_translation_parameters' => $this->getTranslationParameters(),
        ];
    }

    public function getTranslationDomain(): ?string
    {
        return null;
    }

    public function getTranslationParameters(): array
    {
        return [];
    }

    public function getFormClass(): string
    {
        return ConfirmActionType::class;
    }

    public function getForm(): FormInterface
    {
        return $this->formFactory->create($this->getFormClass(), null, $this->getFormOptions());
    }

    /**
     * @param Request $request
     * @param callable $confirmedActionUrlCallback
     * @param null|callable $cancelledActionUrlCallback if omitted, will use the same callback as for confirmed
     * @return array|RedirectResponse
     */
    public function controller(Request $request, callable $confirmedActionUrlCallback, callable $cancelledActionUrlCallback = null)
    {
        $form = $this->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $confirm = $form->get('confirm');
            if ($confirm instanceof SubmitButton && $confirm->isClicked()) {
                if ($form->isValid()) {
                    $this->doConfirmedAction($form->getData());
                    $redirectUrl = $confirmedActionUrlCallback();

                    $this->flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $this->getConfirmedBanner());
                    return new RedirectResponse($redirectUrl);
                }
            } else {
                $this->flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $this->getCancelledBanner());
                return new RedirectResponse($cancelledActionUrlCallback ? $cancelledActionUrlCallback() : $confirmedActionUrlCallback());
            }
        }

        return array_merge($this->getExtraViewData(), [
            'translation_domain' => $this->getTranslationDomain(),
            'translation_prefix' => $this->getTranslationKeyPrefix(),
            'translation_parameters' => $this->getTranslationParameters(),
            'subject' => $this->getSubject(),
            'form' => $form->createView(),
        ]);
    }

    public function getExtraViewData(): array
    {
        return [];
    }
}