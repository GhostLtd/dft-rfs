<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRoUser;
use App\Form\Admin\RoRo\OperatorUserType;
use App\Utility\ConfirmAction\RoRo\Admin\DeleteUserConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/operators', name: 'admin_operators_')]
class OperatorUserController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected DeleteUserConfirmAction $deleteUserConfirmAction
    ) {}

    #[Route(path: '/{operatorId}/users/create', name: 'add_user')]
    public function addUser(
        Request $request,
        #[MapEntity(expr: "repository.findOneById(operatorId)")]
        Operator $operator
    ): Response
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
            'form' => $form,
            'operator' => $operator,
            'translation_parameters' => [
                'code' => $operator->getCode(),
                'name' => $operator->getName(),
            ],
        ]);
    }

    #[Route(path: '/{operatorId}/users/{userId}/delete', name: 'delete_user')]
    public function deleteUser(
        Request $request,
        #[MapEntity(expr: "repository.findOneById(operatorId)")]
        Operator $operator,
        #[MapEntity(expr: "repository.findOneByOperatorIdAndUserId(operatorId, userId)")]
        RoRoUser $user
    ): Response
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
