<?php

namespace App\Utility\ConfirmAction;

use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractConfirmAction implements ConfirmActionInterface
{
    protected $subject;
    protected array $extraViewData;

    public function __construct(protected FormFactoryInterface $formFactory, protected RequestStack $requestStack, protected TranslatorInterface $translator)
    {
        $this->extraViewData = [];
    }

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    #[\Override]
    public function getConfirmedBanner(): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.success'),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.confirmed-notification.heading"),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.confirmed-notification.content"),
            ['style' => NotificationBanner::STYLE_SUCCESS]
        );
    }

    #[\Override]
    public function getFailedBanner(): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.failed'),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.failed-notification.heading"),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.failed-notification.content"),
            ['style' => NotificationBanner::STYLE_WARNING]
        );
    }

    #[\Override]
    public function getCancelledBanner(): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.important'),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.cancelled-notification.heading"),
            $this->domainTrans("{$this->getTranslationKeyPrefix()}.cancelled-notification.content"),
        );
    }

    protected function domainTrans($key): string
    {
        return $this->translator->trans($key, $this->getTranslationParameters(), $this->getTranslationDomain());
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return [
            'translation_domain' => $this->getTranslationDomain(),
            'translation_key_prefix' => $this->getTranslationKeyPrefix(),
            'label_translation_parameters' => $this->getTranslationParameters(),
        ];
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return null;
    }

    #[\Override]
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
     * @param null|callable $cancelledActionUrlCallback if omitted, will use the same callback as for confirmed
     * @return array|RedirectResponse
     */
    public function controller(Request $request, callable $confirmedActionUrlCallback, callable $cancelledActionUrlCallback = null, callable $failedActionUrlCallback = null): array|RedirectResponse
    {
        $form = $this->getForm();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $confirm = $form->get('confirm');

            $session = $this->requestStack->getSession();
            $flashBag = ($session instanceof FlashBagAwareSessionInterface) ?
                $session->getFlashBag() :
                null;

            if ($confirm instanceof SubmitButton && $confirm->isClicked()) {
                if ($form->isValid()) {
                    try {
                        $this->doConfirmedAction($form->getData());
                        $redirectUrl = $confirmedActionUrlCallback();

                        $flashBag?->add(NotificationBanner::FLASH_BAG_TYPE, $this->getConfirmedBanner());
                    }
                    catch(ActionFailedException)
                    {
                        $redirectUrl = $failedActionUrlCallback ? $failedActionUrlCallback() : $confirmedActionUrlCallback();
                        $flashBag?->add(NotificationBanner::FLASH_BAG_TYPE, $this->getFailedBanner());
                    }

                    return new RedirectResponse($redirectUrl);
                }
            } else {
                $flashBag?->add(NotificationBanner::FLASH_BAG_TYPE, $this->getCancelledBanner());
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

    #[\Override]
    public function getExtraViewData(): array
    {
        return $this->extraViewData;
    }

    #[\Override]
    public function setExtraViewData(array $extraViewData): static
    {
        $this->extraViewData = $extraViewData;
        return $this;
    }
}
