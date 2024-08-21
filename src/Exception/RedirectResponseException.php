<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectResponseException extends \Exception
{
    public function __construct(private RedirectResponse $redirectResponse, $message = '', $code = 0, \Exception $previousException = null) {
        parent::__construct($message, $code, $previousException);
    }

    public function getRedirectResponse(): RedirectResponse
    {
        return $this->redirectResponse;
    }
}