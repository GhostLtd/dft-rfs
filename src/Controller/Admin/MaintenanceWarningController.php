<?php

namespace App\Controller\Admin;

use App\Entity\Utility\MaintenanceWarning;
use App\Form\Admin\MaintenanceWarningType;
use App\ListPage\MaintenanceWarningListPage;
use App\Utility\ConfirmAction\DeleteMaintenanceWarningConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/maintenance-warning", name="admin_maintenance_warning_")
 */
class MaintenanceWarningController extends AbstractController
{
    /**
     * @Route("", name="list")
     */
    public function list(MaintenanceWarningListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/maintenance_warning/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit")
     */
    public function edit(Request $request, EntityManagerInterface $entityManager, Session $session, MaintenanceWarning $maintenanceWarning): Response
    {
        /** @var Form $form */
        $form = $this->createForm(MaintenanceWarningType::class, $maintenanceWarning);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->getClickedButton()->getName() === 'cancel') {
                return $this->redirectToRoute('admin_maintenance_warning_list');
            }

            if ($form->isValid()) {
                if (!$form->getData()->getId()) {
                    $entityManager->persist($form->getData());
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Maintenance warning added', 'The new maintenance warning has been added', ['style' => NotificationBanner::STYLE_SUCCESS]));
                } else {
                    $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner('Success', 'Maintenance warning updated', 'The maintenance warning has been updated', ['style' => NotificationBanner::STYLE_SUCCESS]));
                }
                $entityManager->flush();
                return $this->redirectToRoute('admin_maintenance_warning_list');
            }
        }

        return $this->render('admin/maintenance_warning/edit.html.twig', [
            'form' => $form->createView(),
            'maintenanceWarning' => $form->getData(),
        ]);
    }

    /**
     * @Route("/add", name="add")
     */
    public function add(Request $request, EntityManagerInterface $entityManager, Session $session): Response
    {
        return $this->edit($request, $entityManager, $session, new MaintenanceWarning());
    }

    /**
     * @Route("{id}/delete", name="delete")
     * @Template("admin/maintenance_warning/delete.html.twig")
     */
    public function delete(Request $request, DeleteMaintenanceWarningConfirmAction $deleteMaintenanceWarningConfirmAction, MaintenanceWarning $maintenanceWarning)
    {
        return $deleteMaintenanceWarningConfirmAction
            ->setSubject($maintenanceWarning)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_maintenance_warning_list')
            );
    }
}