<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Form\Admin\PreEnquiry\Edit\ResponseType;
use App\Security\Voter\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/pre-enquiry/{preEnquiryId}")
 * @Entity("preEnquiry", expr="repository.find(preEnquiryId)")
 * @IsGranted(AdminSurveyVoter::EDIT, subject="preEnquiry")
 */
class PreEnquiryController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_pre_enquiry_';
    public const ADD_ROUTE = self::ROUTE_PREFIX.'add';
    public const ADD_NOTE_ROUTE = self::ROUTE_PREFIX.'addnote';
    public const DELETE_NOTE_ROUTE = self::ROUTE_PREFIX.'deletenote';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const TRANSITION_ROUTE = self::ROUTE_PREFIX.'transition';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';

    public const EDIT_RESPONSE_ROUTE = self::ROUTE_PREFIX.'edit_response';
    public const ENTER_RESPONSE_ROUTE = self::ROUTE_PREFIX.'enter_response';

    public const RESET_PASSCODE_ROUTE = self::ROUTE_PREFIX.'reset_passcode';
    public const RESET_PASSCODE_SUCCESS_ROUTE = self::ROUTE_PREFIX.'reset_passcode_success';

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/edit-response", name=self::EDIT_RESPONSE_ROUTE)
     */
    public function enterResponse(PreEnquiry $preEnquiry): Response
    {
        $redirectUrl = $this->getRedirectUrl($preEnquiry, 'tab-response');
        return $this->handleRequest($this->getResponse($preEnquiry), ResponseType::class, 'admin/pre-enquiry/edit-response.html.twig', $redirectUrl);
    }

    /**
     * @Route("/enter-response", name=self::ENTER_RESPONSE_ROUTE)
     */
    public function enterInitialDetails(PreEnquiry $preEnquiry): Response
    {
        $redirectUrl = $this->getRedirectUrl($preEnquiry);
        $response = (new PreEnquiryResponse())
            ->setPreEnquiry($preEnquiry);

        $this->entityManager->persist($response);

        return $this->handleRequest($response, ResponseType::class, 'admin/pre-enquiry/enter-response.html.twig', $redirectUrl);
    }

    protected function handleRequest(PreEnquiryResponse $response, string $formClass, string $templateName, string $redirectUrl)
    {
        $form = $this->createForm($formClass, $response);
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($redirectUrl);
            };

            if ($form->isValid()) {
                $this->entityManager->flush();
                return new RedirectResponse($redirectUrl);
            }
        }

        return $this->render($templateName, [
            'preEnquiry' => $response->getPreEnquiry(),
            'form' => $form->createView(),
        ]);
    }

    protected function getRedirectUrl(PreEnquiry $preEnquiry, string $hash = null): string
    {
        return $this->generateUrl(self::VIEW_ROUTE, ['preEnquiryId' => $preEnquiry->getId()]) . ($hash ? ("#".$hash) : null);
    }

    protected function getResponse(PreEnquiry $preEnquiry): PreEnquiryResponse
    {
        $response = $preEnquiry->getResponse();

        if (!$response) {
            throw new NotFoundHttpException();
        }

        return $response;
    }
}