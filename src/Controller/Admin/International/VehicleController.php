<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\VehicleType;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\ConfirmAction\International\DeleteVehicleConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs")
 */
class VehicleController extends AbstractController
{
    private const ROUTE_PREFIX = "admin_international_vehicle_";

    public const ADD_ROUTE = self::ROUTE_PREFIX."add";
    public const DELETE_ROUTE = self::ROUTE_PREFIX . "delete";
    public const EDIT_ROUTE = self::ROUTE_PREFIX."edit";

    protected EntityManagerInterface $entityManager;
    protected RequestStack $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/survey/{surveyId}/add-vehicle", name=self::ADD_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
     */
    public function add(Survey $survey): Response
    {
        $response = $survey->getResponse();

        if (!$response) {
            throw new NotFoundHttpException();
        }

        $vehicle = (new Vehicle())->setSurveyResponse($response);
        $this->entityManager->persist($vehicle);

        return $this->handleRequest($vehicle, 'admin/international/vehicle/add.html.twig', ['placeholders' => true]);
    }

    /**
     * @Route("/vehicle/{vehicleId}/edit", name=self::EDIT_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     */
    public function edit(Vehicle $vehicle): Response
    {
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $vehicle->getSurveyResponse()->getSurvey());
        return $this->handleRequest($vehicle, 'admin/international/vehicle/edit.html.twig');
    }

    protected function handleRequest(Vehicle $vehicle, string $template, array $formOptions = []): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedVehicle = clone $vehicle;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse(
                $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $vehicle->getSurveyResponse()->getSurvey()->getId()]) .
                "#{$vehicle->getId()}"
            );

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            };

            if ($form->isValid()) {
                $this->entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render($template, [
            'vehicle' => $unmodifiedVehicle,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/vehicle/{vehicleId}/delete", name=self::DELETE_ROUTE)
     * @Entity("vehicle", expr="repository.find(vehicleId)")
     * @Template("admin/international/vehicle/delete.html.twig")
     */
    public function delete(Vehicle $vehicle, Request $request, DeleteVehicleConfirmAction $confirmAction)
    {
        $this->denyAccessUnlessGranted(AdminSurveyVoter::EDIT, $vehicle->getSurveyResponse()->getSurvey());
        $survey = $vehicle->getSurveyResponse()->getSurvey();

        return $confirmAction
            ->setSubject($vehicle)
            ->setExtraViewData([
                'vehicle' => $vehicle,
            ])
            ->controller(
                $request,
                function() use ($survey) {
                    return $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]);
                }
            );
    }
}