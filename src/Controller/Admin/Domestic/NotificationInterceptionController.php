<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\NotificationInterception;
use App\Form\Admin\DomesticSurvey\NotificationInterceptionType;
use App\ListPage\Domestic\NotificationInterceptionListPage;
use App\Utility\ConfirmAction\DeleteNotificationInterceptionConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/csrgt/notification-interception', name: 'admin_domestic_notification_interception_')]
class NotificationInterceptionController extends AbstractController
{
    public const MODE_ADD = 'add';
    public const MODE_EDIT_ADDRESS = 'edit-address';
    public const MODE_EDIT_EMAILS = 'edit-emails';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
        protected TranslatorInterface    $translator
    ) {}

    #[Route(path: '/', name: 'list')]
    public function list(NotificationInterceptionListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/notification_interception/list.html.twig', [
            'add_route' => 'admin_domestic_notification_interception_add',
            'edit_address_route' => 'admin_domestic_notification_interception_edit_address',
            'edit_emails_route' => 'admin_domestic_notification_interception_edit_emails',
            'delete_route' => 'admin_domestic_notification_interception_delete',
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
            'survey_type' => 'domestic',
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'delete')]
    public function delete(Request $request, DeleteNotificationInterceptionConfirmAction $confirmAction, NotificationInterception $notificationInterception): Response
    {
        $data = $confirmAction
            ->setSubject($notificationInterception)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_domestic_notification_interception_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/notification_interception/delete.html.twig", $data);
    }

    #[Route(path: '/edit-address/{id}', name: 'edit_address', defaults: ['mode' => 'edit-address'])]
    #[Route(path: '/edit-emails/{id}', name: 'edit_emails', defaults: ['mode' => 'edit-emails'])]
    #[Route(path: '/add', name: 'add', defaults: ['mode' => 'add'])]
    public function edit(Request $request, ?NotificationInterception $notificationInterception, string $mode): Response
    {
        $form = $this->createForm(NotificationInterceptionType::class, $notificationInterception, [
            'edit_mode' => match ($mode) {
                self::MODE_ADD => NotificationInterceptionType::EDIT_ALL,
                self::MODE_EDIT_ADDRESS => NotificationInterceptionType::EDIT_ADDRESS,
                self::MODE_EDIT_EMAILS => NotificationInterceptionType::EDIT_EMAILS,
            },
        ]);

        $form->handleRequest($request);
        $successUrl = $this->generateUrl('admin_domestic_notification_interception_list');

        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');

            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return new RedirectResponse($successUrl);
            }

            if ($form->isValid()) {
                $notificationInterception = $form->getData();
                $this->entityManager->persist($notificationInterception);
                $this->entityManager->flush();

                if ($mode === self::MODE_ADD) {
                    $banner = new NotificationBanner(
                        $this->translator->trans('common.notification.success'),
                        $this->translator->trans("notification-interception.add.confirmed-notification.heading", [], 'admin'),
                        $this->translator->trans("notification-interception.add.confirmed-notification.content", [], 'admin'),
                        ['style' => NotificationBanner::STYLE_SUCCESS]
                    );

                    $session = $this->requestStack->getSession();
                    if ($session instanceof FlashBagAwareSessionInterface) {
                        $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, $banner);
                    }
                }

                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('admin/notification_interception/edit.html.twig', [
            'form' => $form,
            'type' => $mode,
        ]);
    }
}
