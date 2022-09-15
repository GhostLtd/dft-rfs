<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Form\Admin\PreEnquiry\AddSurveyType;
use App\Utility\PasscodeGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AddController extends AbstractController
{
    /**
     * @Route("/pre-enquiry/add/", name=EditController::ADD_ROUTE)
     */
    public function add(Request $request, EntityManagerInterface $entityManager, PasscodeGenerator $passcodeGenerator): Response
    {
        $form = $this->createForm(AddSurveyType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $this->redirectToRoute('admin_index');
            }

            if ($form->isValid()) {
                /** @var PreEnquiry $preEnquiry */
                $preEnquiry = $form->getData();

                $entityManager->persist($preEnquiry);
                $entityManager->flush();

                return $this->render("admin/pre_enquiry/add-success.html.twig", [
                    'preEnquiry' => $preEnquiry,
                    'password' => $passcodeGenerator->getPasswordForUser($preEnquiry->getPasscodeUser()),
                ]);
            }
        }

        return $this->render("admin/pre_enquiry/add.html.twig", [
            'form' => $form->createView(),
        ]);
    }
}