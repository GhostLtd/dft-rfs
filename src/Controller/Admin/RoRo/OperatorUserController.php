<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use App\Form\Admin\RoRo\OperatorUserType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/operators", name="admin_operators_")
 * @Entity("operator", expr="repository.findOneById(operatorId)")
 */
class OperatorUserController extends AbstractController
{
    protected EntityManagerInterface $entityManager;
    protected \App\Utility\ConfirmAction\RoRo\Admin\DeleteUserConfirmAction $deleteUserConfirmAction;

    public function __construct(EntityManagerInterface $entityManager, \App\Utility\ConfirmAction\RoRo\Admin\DeleteUserConfirmAction $deleteUserConfirmAction)
    {
        $this->entityManager = $entityManager;
        $this->deleteUserConfirmAction = $deleteUserConfirmAction;
    }

    /**
     * @Route("/{operatorId}/users/create", name="add_user")
     */
    public function addUser(Request $request, Operator $operator): Response
    {
        $user = (new RoRoUser())->setOperator($operator);
        $form = $this->createForm(OperatorUserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $cancelButton = $form->get('cancel');
            $redirectResponse = new RedirectResponse($this->generateUrl('admin_operators_view', ['operatorId' => $operator->getId()]) . '#users');

            if ($cancelButton instanceof SubmitButton && $cancelButton->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render('admin/roro/operators/add-user.html.twig', [
            'form' => $form->createView(),
            'operator' => $operator,
            'translation_parameters' => [
                'code' => $operator->getCode(),
                'name' => $operator->getName(),
            ],
        ]);
    }

    /**
     * @Route("/{operatorId}/users/{userId}/delete", name="delete_user")
     * @Entity("user", expr="repository.findOneByOperatorIdAndUserId(operatorId, userId)")
     */
    public function deleteUser(Request $request, Operator $operator, RoRoUser $user): Response
    {
        $data = $this->deleteUserConfirmAction
            ->setSubject($user)
            ->controller(
                $request,
                fn() => $this->generateUrl('admin_operators_view', ['operatorId' => $operator->getId()])
            );

        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return $this->render("admin/roro/operators/delete-user.html.twig", $data);
    }
}