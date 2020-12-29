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
            new RoleMenuItem('dashboard', 'menu.dashboard', $this->router->generate('admin_index'), [], []),
            new MenuDivider(),
            new RoleMenuItem('domestic', 'menu.domestic.gb.root', null, [
                new RoleMenuItem('dashboard', 'menu.domestic.gb.surveys', $this->router->generate('admin_domestic_surveys', ['type' => 'gb']), [], []),
                new RoleMenuItem('dashboard', 'menu.domestic.gb.survey-add', $this->router->generate('admin_domestic_surveys_add', ['type' => 'gb']), [], []),
//                new RoleMenuItem('dashboard', 'menu.domestic.gb.sub-item-2', null, [], []),
            ]),
            new MenuDivider(),
            new RoleMenuItem('domestic', 'menu.domestic.ni.root', null, [
                new RoleMenuItem('dashboard', 'menu.domestic.ni.surveys', $this->router->generate('admin_domestic_surveys', ['type' => 'ni']), [], []),
                new RoleMenuItem('dashboard', 'menu.domestic.ni.survey-add', $this->router->generate('admin_domestic_surveys_add', ['type' => 'ni']), [], []),
//                new RoleMenuItem('dashboard', 'menu.domestic.ni.sub-item-2', null, [], []),
            ]),
            new MenuDivider(),

/*            new RoleMenuItem('international', 'menu.international.root', null, [
                new RoleMenuItem('dashboard', 'menu.international.sub-item-1', $this->router->generate('admin_logout'), [], []),
                new RoleMenuItem('dashboard', 'menu.international.sub-item-2', $this->router->generate('admin_logout'), [], []),
            ]),*/

            new RoleMenuItem('logout', 'menu.logout', $this->router->generate('admin_logout'), [], ['ROLE_ADMIN_FORM_USER']),
        ]);
    }
}