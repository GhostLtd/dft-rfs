<?php

namespace App\Utility\Menu;

use App\Controller\Admin\Domestic\SurveyController as DomSurveyController;
use App\Controller\Admin\Domestic\ExportController as DomExportControllerAlias;
use App\Controller\Admin\International\SurveyController as IntSurveyController;
use App\Controller\Admin\International\ExportController as IntExportControllerAlias;
use App\Controller\Admin\PreEnquiry\PreEnquiryController;
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
            new RoleMenuItem('reports', 'menu.domestic.reports', $this->router->generate('admin_reports_dashboard'), [], []),
            new MenuDivider(),
            new RoleMenuItem('domestic', 'menu.domestic.root', null, [
                new RoleMenuItem('surveys-gb', 'menu.domestic.surveys-gb', $this->router->generate(DomSurveyController::LIST_ROUTE, ['type' => 'gb']), [], []),
                new RoleMenuItem('surveys-ni', 'menu.domestic.surveys-ni', $this->router->generate(DomSurveyController::LIST_ROUTE, ['type' => 'ni']), [], []),
                new RoleMenuItem('dvla-import', 'menu.domestic.dvla-import', $this->router->generate('admin_domestic_importdvla_index'), [], []),
                new RoleMenuItem('export', 'menu.domestic.export', $this->router->generate(DomExportControllerAlias::EXPORT_ROUTE), [], []),
                new RoleMenuItem('add-survey', 'menu.domestic.survey-add', $this->router->generate('admin_domestic_survey_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('international', 'menu.international.root', null, [
                new RoleMenuItem('survey', 'menu.international.surveys', $this->router->generate(IntSurveyController::LIST_ROUTE), [], []),
                new RoleMenuItem('sample-import', 'menu.international.sample-import', $this->router->generate('admin_international_sampleimport_start'), [], []),
                new RoleMenuItem('export', 'menu.international.export', $this->router->generate(IntExportControllerAlias::EXPORT_ROUTE), [], []),
                new RoleMenuItem('add-survey', 'menu.international.survey-add', $this->router->generate('admin_international_survey_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('pre-enquiry', 'menu.pre-enquiry.root', null, [
                new RoleMenuItem('list', 'menu.pre-enquiry.pre-enquiries', $this->router->generate(PreEnquiryController::LIST_ROUTE), [], []),
                new RoleMenuItem('add', 'menu.pre-enquiry.add', $this->router->generate('admin_pre_enquiry_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('maintenance-warnings', 'Maintenance warnings', $this->router->generate('admin_maintenance_warning_list'), [], []),
            new RoleMenuItem('logout', 'menu.logout', $this->router->generate('admin_logout'), [], ['ROLE_ADMIN_FORM_USER']),
        ]);
    }
}