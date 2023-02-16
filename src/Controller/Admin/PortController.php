<?php

namespace App\Controller\Admin;

use App\Entity\Route\ForeignPort;
use App\Entity\Route\PortInterface;
use App\Entity\Route\UkPort;
use App\Form\Admin\PortType;
use App\ListPage\PortListPage;
use App\Utility\ConfirmAction\DeletePortConfirmAction;
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
 * @Route("/ports", name="admin_ports_")
 * @Entity("port", expr="repository.find(portId)")
 */
class PortController extends AbstractController
{
    protected DeletePortConfirmAction $deletePortConfirmAction;
    protected EntityManagerInterface $entityManager;
    protected PortAndRouteUsageHelper $portAndRouteUsageHelper;

    public function __construct(
        DeletePortConfirmAction $deletePortConfirmAction,
        EntityManagerInterface $entityManager,
        PortAndRouteUsageHelper $portAndRouteUsageHelper
    ) {
        $this->deletePortConfirmAction = $deletePortConfirmAction;
        $this->entityManager = $entityManager;
        $this->portAndRouteUsageHelper = $portAndRouteUsageHelper;
    }

    /**
     * @Route("/uk", name="uk_list")
     */
    public function ukList(PortListPage $listPage, Request $request): Response
    {
        return $this->list($listPage, $request, PortListPage::TYPE_PORT_UK);
    }

    /**
     * @Route("/foreign", name="foreign_list")
     */
    public function foreignList(PortListPage $listPage, Request $request): Response
    {
        return $this->list($listPage, $request, PortListPage::TYPE_PORT_FOREIGN);
    }

    protected function list(PortListPage $listPage, Request $request, string $portType): Response
    {
        $listPage
            ->setPortType($portType)
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {#
            return new RedirectResponse($listPage->getClearUrl());
        }

        $listPageData = $listPage->getData();
        $this->portAndRouteUsageHelper->preFetchCountsForPorts(iterator_to_array($listPageData->getEntities()));

        return $this->render('admin/ports/list.html.twig', [
            'data' => $listPageData,
            'form' => $listPage->getFiltersForm()->createView(),
            'portType' => $portType,
        ]);
    }

    /**
     * @Route("/uk/add", name="uk_add")
     */
    public function ukAdd(Request $request): Response
    {
        return $this->addOrEdit($request, PortListPage::TYPE_PORT_UK);
    }

    /**
     * @Route("/uk/{portId}/edit", name="uk_edit")
     * @IsGranted("CAN_EDIT_PORT", subject="port")
     */
    public function ukEdit(Request $request, UkPort $port): Response
    {
        return $this->addOrEdit($request, PortListPage::TYPE_PORT_UK, $port);
    }

    /**
     * @Route("/uk/{portId}/delete", name="uk_delete")
     * @IsGranted("CAN_DELETE_PORT", subject="port")
     */
    public function ukDelete(Request $request, UkPort $port): Response
    {
        return $this->delete($request, $port);
    }

    /**
     * @Route("/foreign/add", name="foreign_add")
     */
    public function foreignAdd(Request $request): Response
    {
        return $this->addOrEdit($request, PortListPage::TYPE_PORT_FOREIGN);
    }

    /**
     * @Route("/foreign/{portId}/edit", name="foreign_edit")
     * @IsGranted("CAN_EDIT_PORT", subject="port")
     */
    public function foreignEdit(Request $request, ForeignPort $port): Response
    {
        return $this->addOrEdit($request, PortListPage::TYPE_PORT_FOREIGN, $port);
    }

    /**
     * @Route("/foreign/{portId}/delete", name="foreign_delete")
     * @IsGranted("CAN_DELETE_PORT", subject="port")
     */
    public function foreignDelete(Request $request, ForeignPort $port): Response
    {
        return $this->delete($request, $port);
    }

    protected function addOrEdit(Request $request, string $portType, PortInterface $port=null): Response
    {
        $isAdd = ($port === null);

        $dataClass = match($portType) {
            PortListPage::TYPE_PORT_FOREIGN => ForeignPort::class,
            PortListPage::TYPE_PORT_UK => UkPort::class,
        };

        $form = $this->createForm(PortType::class, $port, [
            'add' => $isAdd,
            'data_class' => $dataClass,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');

            $redirectPath = $this->getListPagePathForPortClass($dataClass);
            $redirectResponse = new RedirectResponse($redirectPath);

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

        return $this->render('admin/ports/'.($isAdd ? 'add' : 'edit').'.html.twig', [
            'form' => $form->createView(),
            'portType' => $portType,
        ]);
    }

    protected function delete(Request $request, PortInterface $port): Response
    {
        $data = $this->deletePortConfirmAction
            ->setSubject($port)
            ->controller(
                $request,
                fn() => $this->getListPagePathForPortClass(get_class($port))
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/ports/delete.html.twig", $data);
    }

    public function getListPagePathForPortClass(string $dataClass): string
    {
        $route = match ($dataClass) {
            ForeignPort::class => 'admin_ports_foreign_list',
            UkPort::class => 'admin_ports_uk_list',
        };

        return $this->generateUrl($route);
    }
}