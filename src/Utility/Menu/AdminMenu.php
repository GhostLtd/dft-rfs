<?php

namespace App\Utility\Menu;

use App\Controller\Admin\Domestic\SurveyController as DomSurveyController;
use App\Controller\Admin\International\SurveyController as IntSurveyController;
use App\Controller\Admin\PreEnquiry\EditController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;

class AdminMenu implements MenuInterface
{
    use RoleFilterTrait;

    public function __construct(protected Security $security, protected RouterInterface $router)
    {
    }

    /**
     * @return array<int, MenuItemInterface>
     */
    #[\Override]
    public function getMenuItems(): array
    {
        return $this->filterMenuItemsByRole($this->security, [
            new RoleMenuItem('dashboard', 'menu.dashboard', $this->router->generate('admin_index'), [], []),
            new RoleMenuItem('reports', 'menu.reports', $this->router->generate('admin_reports_dashboard'), [], []),
            new RoleMenuItem('feedback', 'menu.feedback', $this->router->generate('admin_surveyfeedback_export_index'), [], []),
            new MenuDivider(),
            new RoleMenuItem('domestic', 'menu.domestic.root', null, [
                new RoleMenuItem('surveys-gb', 'menu.domestic.surveys-gb', $this->router->generate(DomSurveyController::LIST_ROUTE, ['type' => 'gb']), [], []),
                new RoleMenuItem('surveys-ni', 'menu.domestic.surveys-ni', $this->router->generate(DomSurveyController::LIST_ROUTE, ['type' => 'ni']), [], []),
                new RoleMenuItem('dvla-import', 'menu.domestic.dvla-import', $this->router->generate('admin_domestic_importdvla_index'), [], []),
                new RoleMenuItem('export', 'menu.domestic.export', $this->router->generate('admin_domestic_export_list'), [], []),
                new RoleMenuItem('add-survey', 'menu.domestic.survey-add', $this->router->generate('admin_domestic_survey_add'), [], []),
                new RoleMenuItem('driver-availability-export', 'menu.domestic.driver-availability-export', $this->router->generate('admin_domestic_driveravailabilityexport_list'), [], []),
                new RoleMenuItem('notification-interception', 'menu.domestic.notification-interception', $this->router->generate('admin_domestic_notification_interception_list'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('international', 'menu.international.root', null, [
                new RoleMenuItem('survey', 'menu.international.surveys', $this->router->generate(IntSurveyController::LIST_ROUTE), [], []),
                new RoleMenuItem('sample-import', 'menu.international.sample-import', $this->router->generate('admin_international_sampleimport_start'), [], []),
                new RoleMenuItem('export', 'menu.international.export', $this->router->generate('admin_international_export_list'), [], []),
                new RoleMenuItem('add-survey', 'menu.international.survey-add', $this->router->generate('admin_international_survey_add'), [], []),
                new RoleMenuItem('notification-interception', 'menu.international.notification-interception', $this->router->generate('admin_international_notification_interception_list'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('pre-enquiry', 'menu.pre-enquiry.root', null, [
                new RoleMenuItem('list', 'menu.pre-enquiry.pre-enquiries', $this->router->generate(EditController::LIST_ROUTE), [], []),
                new RoleMenuItem('sample-import', 'menu.pre-enquiry.sample-import', $this->router->generate('admin_preenquiry_sampleimport_start'), [], []),
                new RoleMenuItem('export', 'menu.pre-enquiry.export', $this->router->generate('admin_preenquiry_export_list'), [], []),
                new RoleMenuItem('add', 'menu.pre-enquiry.add', $this->router->generate('admin_preenquiry_add'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('roro', 'menu.roro.root', null, [
                new RoleMenuItem('dashboard', 'menu.roro.surveys', $this->router->generate('admin_roro_surveys_list'), [], []),
                new RoleMenuItem('operator-groups', 'menu.roro.operator-groups', $this->router->generate('admin_operator_groups_list'), [], []),
                new RoleMenuItem('operators', 'menu.roro.operators', $this->router->generate('admin_operators_list'), [], []),
                new RoleMenuItem('export', 'menu.roro.export', $this->router->generate('admin_roro_export_list'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('ports-and-routes', 'menu.ports-and-routes.root', null, [
                new RoleMenuItem('ports-uk', 'menu.ports-and-routes.ports.uk', $this->router->generate('admin_ports_uk_list'), [], []),
                new RoleMenuItem('ports-foreign', 'menu.ports-and-routes.ports.foreign', $this->router->generate('admin_ports_foreign_list'), [], []),
                new RoleMenuItem('routes', 'menu.ports-and-routes.routes', $this->router->generate('admin_routes_list'), [], []),
            ]),
            new MenuDivider(),

            new RoleMenuItem('maintenance-warnings', 'Maintenance warnings', $this->router->generate('admin_maintenance_warning_list'), [], []),
            new RoleMenuItem('user-id-lookup', 'User ID lookup', $this->router->generate('admin_user_id_lookup'), [], []),
            new RoleMenuItem('logout', 'menu.logout', $this->router->generate('admin_logout'), [], ['ROLE_ADMIN_FORM_USER']),
        ]);
    }
}
