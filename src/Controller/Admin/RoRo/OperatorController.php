<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Form\Admin\RoRo\OperatorType;
use App\ListPage\RoRo\OperatorListPage;
use App\Repository\RoRo\OperatorRepository;
use App\Utility\ConfirmAction\RoRo\Admin\DeleteOperatorConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/operators", name: "admin_operators_")]
class OperatorController extends AbstractController
{
    public function __construct(protected DeleteOperatorConfirmAction $deleteOperatorConfirmAction, protected EntityManagerInterface $entityManager)
    {
    }

    #[Route("", name: "list")]
    public function list(OperatorListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        $listPageData = $listPage->getData();

        return $this->render('admin/roro/operators/list.html.twig', [
            'data' => $listPageData,
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    #[Route("/add", name: "add")]
    public function add(Request $request): Response
    {
        return $this->addOrEdit($request);
    }

    #[Route("/{operatorId}", name: "view")]
    public function view(
        #[MapEntity(expr: "repository.findOneById(operatorId)")]
        Operator $operator,
        OperatorRepository $operatorRepository
    ): Response
    {
        $operatorGroup = $operatorRepository->findOperatorGroupForOperator($operator);
        $operatorGroupOperators = $operatorGroup ?
            $operatorRepository->findOperatorsWithNamePrefix($operatorGroup->getName()) :
            null;

        return $this->render('admin/roro/operators/view.html.twig', [
            'operator' => $operator,
            'operator_group' => $operatorGroup,
            'operator_group_operators' => $operatorGroupOperators,
            'translation_parameters' => [
                'name' => $operator->getName(),
                'code' => $operator->getCode(),
            ],
        ]);
    }

    #[Route("/{operatorId}/edit", name: "edit")]
    #[IsGranted("CAN_EDIT_OPERATOR", subject: "operator")]
    public function edit(
        Request $request,
        #[MapEntity(expr: "repository.findOneById(operatorId)")]
        Operator $operator
    ): Response
    {
        return $this->addOrEdit($request, $operator);
    }

    protected function addOrEdit(Request $request, Operator $operator=null): Response
    {
        $isAdd = ($operator === null);

        $form = $this->createForm(OperatorType::class, $operator, [
            'add' => $isAdd,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');
            $redirectResponse = $isAdd ?
                $this->redirectToRoute('admin_operators_list') :
                $this->redirectToRoute('admin_operators_view', ['operatorId' => $operator->getId()]);

            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                if ($isAdd) {
                    $this->entityManager->persist($form->getData());
                }

                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render('admin/roro/operators/'.($isAdd ? 'add' : 'edit').'.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route("/{operatorId}/delete", name: "delete")]
    #[IsGranted("CAN_DELETE_OPERATOR", subject: "operator")]
    public function delete(
        Request $request,
        #[MapEntity(expr: "repository.findOneById(operatorId)")]
        Operator $operator
    ): Response
    {
        $data = $this->deleteOperatorConfirmAction
            ->setSubject($operator)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_operators_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/roro/operators/delete.html.twig", $data);
    }
}
