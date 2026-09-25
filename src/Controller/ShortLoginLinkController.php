<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Redirects HTTPS short login links to the login page with the token unchanged.
 * The login page decodes the token, including handling invalid tokens.
 */
class ShortLoginLinkController extends AbstractController
{
    #[Route(path: '/c/{token}', name: 'app_login_short', requirements: ['token' => '[^/]+'], methods: ['GET'], schemes: ['https'])]
    public function __invoke(string $token): RedirectResponse
    {
        return $this->redirectToRoute('app_login', ['c' => $token]);
    }
}
