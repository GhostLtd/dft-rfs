<?php

namespace App\Controller\PreEnquiry;

use App\Entity\PasscodeUser;
use App\Form\PreEnquiry\SubmitPreEnquiryType;
use App\Workflow\PreEnquiry\PreEnquiryState;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class PreEnquiryController extends AbstractController
{
    public const WIZARD_ROUTE = 'app_pre_enquiry_wizard';
    public const SUMMARY_ROUTE = 'app_pre_enquiry_summary';

    /**
     * @Route("/pre-enquiry", name=PreEnquiryController::SUMMARY_ROUTE)
     */
    public function index(Request $request, UserInterface $user, EntityManagerInterface $entityManager): Response
    {
        assert($user instanceof PasscodeUser);
        $preEnquiry = $user->getPreEnquiry();

        dump($preEnquiry);

        if ($preEnquiry->getSubmissionDate()) {
            return $this->render('pre_enquiry/thanks.html.twig');
        }

        if (!$preEnquiry->getResponse()) {
            return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => PreEnquiryState::STATE_INTRODUCTION]);
        }

        $form = $this->createForm(SubmitPreEnquiryType::class);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $button = $form->get('submit');
            if ($button instanceof SubmitButton && $button->isClicked()) {
                $preEnquiry->setSubmissionDate(new DateTime());
                $entityManager->flush();

                return $this->redirectToRoute(self::SUMMARY_ROUTE);
            }
        }

        return $this->render('pre_enquiry/summary.html.twig', [
            'preEnquiry' => $preEnquiry,
            'form' => $form->createView(),
        ]);
    }
}
