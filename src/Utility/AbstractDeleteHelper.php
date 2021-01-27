<?php

namespace App\Utility;

use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractDeleteHelper
{
    protected EntityManagerInterface $entityManager;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function getDeletedNotification(string $prefix): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.success'),
            $this->translator->trans("{$prefix}.deleted.heading"),
            $this->translator->trans("{$prefix}.deleted.content"),
            ['style' => NotificationBanner::STYLE_SUCCESS]
        );
    }

    public function getCancelledNotification(string $prefix): NotificationBanner
    {
        return new NotificationBanner(
            $this->translator->trans('common.notification.important'),
            $this->translator->trans("{$prefix}.cancelled.heading"),
            $this->translator->trans("{$prefix}.cancelled.content")
        );
    }
}