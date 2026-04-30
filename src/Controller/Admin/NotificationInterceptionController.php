<?php

namespace App\Controller\Admin;

use App\Entity\NotificationInterception;
use App\Form\Admin\LCNI\NotificationInterceptionType;
use App\ListPage\NotificationInterceptionListPage;
use App\Utility\ConfirmAction\DeleteNotificationInterceptionConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/notification-interception', name: 'admin_notification_interception_')]
class NotificationInterceptionController extends AbstractController
{
    public const string MODE_ADD = 'add';
    public const string MODE_EDIT_ALL = 'edit-all';
    public const string MODE_EDIT_EMAILS = 'edit-emails';
    public const string MODE_EDIT_NAMES = 'edit-names';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack           $requestStack,
    ) {}

    #[Route(path: '', name: 'list')]
    public function list(NotificationInterceptionListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/notification_interception/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route(path: '/delete/{id}', name: 'delete')]
    public function delete(Request $request, DeleteNotificationInterceptionConfirmAction $confirmAction, NotificationInterception $notificationInterception): Response
    {
        $data = $confirmAction
            ->setSubject($notificationInterception)
            ->controller(
                $request,
                $this->generateUrl('admin_notification_interception_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/notification_interception/delete.html.twig", $data);
    }

//    #[Route(path: '/edit-names/{id}', name: 'edit_names', defaults: ['mode' => 'edit-names'])]
//    #[Route(path: '/edit-emails/{id}', name: 'edit_emails', defaults: ['mode' => 'edit-emails'])]
    #[Route(path: '/edit/{id}', name: 'edit_all', defaults: ['mode' => 'edit-all'])]
    #[Route(path: '/add', name: 'add', defaults: ['mode' => 'add'])]
    public function edit(Request $request, ?NotificationInterception $notificationInterception, string $mode): Response
    {
        $successUrl = $this->generateUrl('admin_notification_interception_list');
        $form = $this->createForm(NotificationInterceptionType::class, $notificationInterception, [
            'edit_mode' => match ($mode) {
                self::MODE_ADD, self::MODE_EDIT_ALL => NotificationInterceptionType::EDIT_ALL,
                self::MODE_EDIT_NAMES => NotificationInterceptionType::EDIT_NAMES,
                self::MODE_EDIT_EMAILS => NotificationInterceptionType::EDIT_EMAILS,
            },
            'cancel_url' => $successUrl,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $notificationInterception = $form->getData();
                $this->entityManager->persist($notificationInterception);
                $this->entityManager->flush();

                if ($mode === self::MODE_ADD) {
                    $banner = new NotificationBanner(
                        'notification.success',
                        new TranslatableMessage("notification-interception.add.confirmed-notification.heading", [], 'admin'),
                        new TranslatableMessage("notification-interception.add.confirmed-notification.content", [], 'admin'),
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
