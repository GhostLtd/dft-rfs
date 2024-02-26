<?php

namespace App\Utility\AlphagovNotify;

use Alphagov\Notifications\Client as AlphagovClient;
use GuzzleHttp\Client;

class ClientFactory
{
    public function __invoke($apiKey): AlphagovClient
    {
        return new AlphagovClient([
            'apiKey' => $apiKey,
            'httpClient' => new Client(),
        ]);
    }
}
