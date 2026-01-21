<?php

namespace App\Controller\Admin;

use App\Entity\EmailBlacklist;
use App\Form\Admin\EmailBlacklistType;
use App\ListPage\EmailBlacklistListPage;
use App\Utility\ConfirmAction\DeleteEmailBlacklistConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/email-blacklist', name: 'admin_email_blacklist_')]
class EmailBlacklistController extends AbstractController
{
    public function __construct(
        private EmailBlacklistListPage $listPage,
        private DeleteEmailBlacklistConfirmAction $deleteConfirmAction,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $this->listPage->handleRequest($request);

        if ($this->listPage->isClearClicked()) {
            return $this->redirect($this->listPage->getClearUrl());
        }

        return $this->render('admin/email_blacklist/list.html.twig', [
            'data' => $this->listPage->getData(),
            'form' => $this->listPage->getFiltersForm(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(Request $request): Response
    {
        $emailBlacklist = new EmailBlacklist();
        $form = $this->createForm(EmailBlacklistType::class, $emailBlacklist);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('buttons')->get('cancel')->isClicked()) {
                return $this->redirectToRoute('admin_email_blacklist_list');
            }

            if ($form->isValid()) {
                $emailBlacklist->setCreatedAt(new \DateTime());

                $this->entityManager->persist($emailBlacklist);
                $this->entityManager->flush();

                $this->addFlash('success', 'email-blacklist.add.success');

                return $this->redirectToRoute('admin_email_blacklist_list');
            }
        }

        return $this->render('admin/email_blacklist/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EmailBlacklist $emailBlacklist): Response
    {
        $form = $this->createForm(EmailBlacklistType::class, $emailBlacklist, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('buttons')->get('cancel')->isClicked()) {
                return $this->redirectToRoute('admin_email_blacklist_list');
            }

            if ($form->isValid()) {
                $this->entityManager->flush();

                $this->addFlash('success', 'email-blacklist.edit.success');

                return $this->redirectToRoute('admin_email_blacklist_list');
            }
        }

        return $this->render('admin/email_blacklist/edit.html.twig', [
            'form' => $form,
            'emailBlacklist' => $emailBlacklist,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Request $request, EmailBlacklist $emailBlacklist): Response
    {
        $data = $this->deleteConfirmAction
            ->setSubject($emailBlacklist)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_email_blacklist_list'),
                fn() => $this->generateUrl('admin_email_blacklist_list'),
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render('admin/email_blacklist/delete.html.twig', $data);
    }
}
