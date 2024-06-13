<?php


namespace App\Utility\ConfirmAction;


use Ghost\GovUkFrontendBundle\Model\NotificationBanner;

interface ConfirmActionInterface
{
    public function getSubject();
    public function getTranslationKeyPrefix(): string;
    public function getTranslationDomain(): ?string;
    public function getTranslationParameters(): array;
    public function getFormOptions(): array;
    public function doConfirmedAction($formData);
    public function getCancelledBanner(): NotificationBanner;
    public function getConfirmedBanner(): NotificationBanner;
    public function getFailedBanner(): NotificationBanner;
    public function getExtraViewData(): array;
    public function setExtraViewData(array $extraViewData): self;
}
