<?php

namespace App\Utility;

class CspInlineScriptHelper
{
    private array $nonces = [];

    public function __construct(protected string $secret)
    {}

    /**
     * @param string $context a context identifier for this nonce
     */
    public function getNonce(string $context): string
    {
        if (!isset($this->nonces[$context])) {
            $rand = random_int(0, mt_getrandmax());
            $this->nonces[$context] = hash_hmac('sha1', "$context-$rand", $this->secret);
        }
        return $this->nonces[$context];
    }
}