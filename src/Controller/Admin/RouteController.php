<?php

namespace App\Controller\Admin;

use App\Entity\Route\Route as RouteEntity;
use App\Form\Admin\RouteType;
use App\ListPage\RouteListPage;
use App\Utility\ConfirmAction\DeleteRouteConfirmAction;
use App\Utility\RoRo\PortAndRouteUsageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/routes", name="admin_routes_")
 * @Entity("route", expr="repository.findOneById(routeId)")
 */
class RouteController extends AbstractController
{

    protected DeleteRouteConfirmAction $deleteRouteConfirmAction;
    protected EntityManagerInterface $entityManager;
    protected PortAndRouteUsageHelper $portAndRouteUsageHelper;

    public function __construct(DeleteRouteConfirmAction $deleteRouteConfirmAction, EntityManagerInterface $entityManager, PortAndRouteUsageHelper $portAndRouteUsageHelper)
    {
        $this->deleteRouteConfirmAction = $deleteRouteConfirmAction;
        $this->entityManager = $entityManager;
        $this->portAndRouteUsageHelper = $portAndRouteUsageHelper;
    }

    /**
     * @Route("", name="list")
     */
    public function list(RouteListPage $listPage, Request $request): Response
    {
        $listPage->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        $listPageData = $listPage->getData();
        $this->portAndRouteUsageHelper->preFetchCountsForRoutes(iterator_to_array($listPageData->getEntities()));

        return $this->render('admin/routes/list.html.twig', [
            'data' => $listPageData,
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/{routeId}/view", name="view")
     */
    public function view(RouteEntity $route): Response
    {
        return $this->render('admin/roro/routes/view.html.twig', [
            'route' => $route,
            'translation_parameters' => [
                'uk_port_name' => $route->getUkPort()->getName(),
                'foreign_port_name' => $route->getForeignPort()->getName(),
            ],
        ]);
    }

    /**
     * @Route("/add", name="add")
     */
    public function add(Request $request): Response
    {
        return $this->addOrEdit($request);
    }

    /**
     * @Route("/{routeId}/edit", name="edit")
     * @IsGranted("CAN_EDIT_ROUTE", subject="route")
     */
    public function edit(Request $request, RouteEntity $route): Response
    {
        return $this->addOrEdit($request, $route);
    }

    protected function addOrEdit(Request $request, RouteEntity $route=null): Response
    {
        $isAdd = ($route === null);

        $form = $this->createForm(RouteType::class, $route, [
            'add' => $isAdd,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');
            $redirectResponse = $isAdd ?
                $this->redirectToRoute('admin_routes_list') :
                $this->redirectToRoute('admin_routes_view', ['routeId' => $route->getId()]);

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

        return $this->render('admin/routes/'.($isAdd ? 'add' : 'edit').'.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{routeId}/delete", name="delete")
     * @IsGranted("CAN_DELETE_ROUTE", subject="route")
     */
    public function delete(Request $request, RouteEntity $route): Response
    {
        $data = $this->deleteRouteConfirmAction
            ->setSubject($route)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_routes_list')
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/routes/delete.html.twig", $data);
    }
}