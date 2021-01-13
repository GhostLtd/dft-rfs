<?php

namespace App\Utility\Menu;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

class AdminMenu implements MenuInterface
{
    use RoleFilterTrait;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $security;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * NavBar constructor.
     * @param Security $security
     * @param RouterInterface $router
     */
    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * @return MenuItemInterface[]
     */
    public function getMenuItems()
    {
        return $this->filterMenuItemsByRole($this->security, [
            new RoleMenuItem('dashboard', 'menu.dashboard', $this->router->generate('admin_index'), [], []),
            new MenuDivider(),
            new RoleMenuItem('domestic', 'menu.domestic.root', null, [
                new RoleMenuItem('surveys-gb', 'menu.domestic.surveys-gb', $this->router->generate('admin_domestic_surveys', ['type' => 'gb']), [], []),
                new RoleMenuItem('surveys-ni', 'menu.domestic.surveys-ni', $this->router->generate('admin_domestic_surveys', ['type' => 'ni']), [], []),
                new RoleMenuItem('dvla-import', 'menu.domestic.dvla-import', $this->router->generate('admin_domestic_importdvla_index'), [], []),
                new RoleMenuItem('add-survey', 'menu.domestic.survey-add', $this->router->generate('admin_domestic_survey_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('international', 'menu.international.root', null, [
                new RoleMenuItem('survey', 'menu.international.surveys', $this->router->generate('admin_international_survey_list'), [], []),
                new RoleMenuItem('add-survey', 'menu.international.survey-add', $this->router->generate('admin_international_survey_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('logout', 'menu.logout', $this->router->generate('admin_logout'), [], ['ROLE_ADMIN_FORM_USER']),
        ]);
    }
}