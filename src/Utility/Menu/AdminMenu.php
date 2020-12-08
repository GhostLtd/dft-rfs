<?php

namespace App\Utility\Menu;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminMenu implements MenuInterface
{
    use RoleFilterTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * NavBar constructor.
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param RouterInterface $router
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(AuthorizationCheckerInterface $authorizationChecker, RouterInterface $router, TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return MenuItemInterface[]
     */
    public function getMenuItems()
    {
        return $this->filterMenuItemsByRole($this->authorizationChecker, [
            new RoleMenuItem('dashboard', 'home.menu', $this->router->generate('admin_index'), [], []),
            new MenuDivider(),
        ]);
    }
}