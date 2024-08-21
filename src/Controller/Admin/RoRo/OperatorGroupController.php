<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\OperatorGroup;
use App\ListPage\RoRo\OperatorGroupListPage;
use App\Repository\RoRo\OperatorRepository;
use App\Utility\ConfirmAction\RoRo\Admin\DeleteOperatorGroupConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/operator-groups", name: "admin_operator_groups_")]
class OperatorGroupController extends AbstractController
{
    public function __construct(
        protected DeleteOperatorGroupConfirmAction $deleteOperatorGroupConfirmAction,
        protected EntityManagerInterface           $entityManager,
        protected OperatorRepository               $operatorRepository,
    ) {}

    #[Route("", name: "list")]
    public function list(OperatorGroupListPage $listPage, Request $request): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        $listPageData = $listPage->getData();

        return $this->render('admin/roro/operator-groups/list.html.twig', [
            'data' => $listPageData,
            'form' => $listPage->getFiltersForm(),
        ]);
    }

    // Note: The requirement can be replaced after upgrading to Symfony 6.1
    //       https://symfony.com/blog/new-in-symfony-6-1-improved-routing-requirements-and-utf-8-parameters#a-collection-of-common-routing-requirements
    #[Route("/{operatorGroupId}", name: "view", requirements: ['operatorGroupId' => '[0-9a-f]{8}-[0-9a-f]{4}-[1-6][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}'])]
    public function view(
        #[MapEntity(expr: "repository.findOneById(operatorGroupId)")]
        OperatorGroup $operatorGroup
    ): Response
    {
        return $this->render('admin/roro/operator-groups/view.html.twig', [
            'operatorGroup' => $operatorGroup,
            'operatorsInGroup' => $this->operatorRepository->findOperatorsWithNamePrefix($operatorGroup->getName()),
            'translation_parameters' => [
                'name' => $operatorGroup->getName(),
            ],
        ]);
    }

    #[Route("/{operatorGroupId}/delete", name: "delete")]
    #[IsGranted("CAN_DELETE_OPERATOR_GROUP", subject: "operatorGroup")]
    public function delete(
        Request       $request,
        #[MapEntity(expr: "repository.findOneById(operatorGroupId)")]
        OperatorGroup $operatorGroup
    ): Response
    {
        $data = $this->deleteOperatorGroupConfirmAction
            ->setSubject($operatorGroup)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_operator_groups_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/roro/operator-groups/delete.html.twig", $data);
    }
}
