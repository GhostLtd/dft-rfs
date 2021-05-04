<?php


namespace App\Messenger\AlphagovNotify;


interface ApiResponseInterface
{
    public function getNotifyApiResponses(): ?array;
    public function setNotifyApiResponse(string $key, array $notifyApiResponse): self;
}