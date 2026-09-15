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
    public function addNotifyApiResponse(NotifyApiResponse $notifyApiResponse): static;

    // Used in the templates
    public function getNotifyApiResponsesMatching(string $eventName, string $notificationClass, bool $sorted = false, bool $reverse = false): ?array;
}