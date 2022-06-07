<?php

namespace App\Messenger\AlphagovNotify;

use App\Entity\NotifyApiResponse;
use Doctrine\Common\Collections\Collection;

interface ApiResponseInterface
{
    /**
     * @return Collection|NotifyApiResponse[]
     */
    public function getNotifyApiResponses(): Collection;
    public function addNotifyApiResponse(NotifyApiResponse $notifyApiResponse): object;

    // Used in the templates
    public function getNotifyApiResponsesMatching(string $eventName, string $notificationClass): ?array;
}