<?php

namespace App\Controller\Admin\International;

use App\Entity\International\NotificationInterception;
use App\Form\Admin\InternationalSurvey\NotificationInterceptionType;
use App\ListPage\International\NotificationInterceptionListPage;
use App\Utility\ConfirmAction\DeleteNotificationInterceptionConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/irhs/notification-interception", name="admin_international_notification_interception_")
 */
class NotificationInterceptionController extends AbstractController
{
    const TYPE_ADD = 'add';
    const TYPE_EDIT = 'edit';

    protected EntityManagerInterface $entityManager;
    protected FlashBagInterface $flashBag;
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $entityManager, FlashBagInterface $flashBag, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->flashBag = $flashBag;
        $this->translator = $translator;
    }

    /**
     * @Route("/", name="list")
     */
    public function list(NotificationInterceptionListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/notification_interception/list.html.twig', [
            'add_route' => 'admin_international_notification_interception_add',
            'edit_route' => 'admin_international_notification_interception_edit',
            'delete_route' => 'admin_international_notification_interception_delete',
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="delete")
     */
    public function delete(Request $request, DeleteNotificationInterceptionConfirmAction $confirmAction, NotificationInterception $notificationInterception): Response
    {
        $data = $confirmAction
            ->setSubject($notificationInterception)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_international_notification_interception_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/notification_interception/delete.html.twig", $data);
    }

    /**
     * @Route("/edit/{id}", name="edit")
     * @Route("/add", name="add")
     */
    public function edit(Request $request, ?NotificationInterception $notificationInterception): Response
    {
        $type = is_null($notificationInterception) ? self::TYPE_ADD : self::TYPE_EDIT;

        $form = $this->createForm(NotificationInterceptionType::class, $notificationInterception);
        $form->handleRequest($request);
        $successUrl = $this->generateUrl('admin_international_notification_interception_list');

        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');

            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return new RedirectResponse($successUrl);
            }

            if ($form->isValid()) {
                $notificationInterception = $form->getData();
                $this->entityManager->persist($notificationInterception);
                $this->entityManager->flush();

                if ($type === self::TYPE_ADD) {
                    $banner = new NotificationBanner(
                        $this->translator->trans('common.notification.success'),
                        $this->translator->trans("notification-interception.add.confirmed-notification.heading", [], 'admin'),
                        $this->translator->trans("notification-interception.add.confirmed-notification.content", [], 'admin'),
                        ['style' => NotificationBanner::STYLE_SUCCESS]
                    );

                    $this->flashBag->add(NotificationBanner::FLASH_BAG_TYPE, $banner);
                }

                return new RedirectResponse($successUrl);
            }
        }

        return $this->render('admin/notification_interception/edit.html.twig', [
            'form' => $form->createView(),
            'type' => $type,
        ]);
    }
}