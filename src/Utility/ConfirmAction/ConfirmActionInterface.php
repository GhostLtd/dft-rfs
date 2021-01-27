<?php


namespace App\Utility\ConfirmAction;


use Ghost\GovUkFrontendBundle\Model\NotificationBanner;

interface ConfirmActionInterface
{
    public function getSubject();
    public function getTranslationKeyPrefix(): string;
    public function getTranslationDomain(): ?string;
    public function getFormOptions(): array;
    public function doConfirmedAction();
    public function getConfirmedBanner(): NotificationBanner;
    public function getCancelledBanner(): NotificationBanner;
}
