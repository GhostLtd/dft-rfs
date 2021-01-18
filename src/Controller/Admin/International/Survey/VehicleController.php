<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Survey;
use App\Entity\International\Vehicle;
use App\Form\Admin\InternationalSurvey\VehicleType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
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
    public const EDIT_ROUTE = self::ROUTE_PREFIX."edit";

    protected $entityManager;
    protected $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    /**
     * @Route("/survey/{surveyId}/add-vehicle", name=self::ADD_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
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
        return $this->handleRequest($vehicle, 'admin/international/vehicle/edit.html.twig');
    }

    protected function handleRequest(Vehicle $vehicle, string $template, array $formOptions = []): Response
    {
        $form = $this->createForm(VehicleType::class, $vehicle, $formOptions);
        $request = $this->requestStack->getCurrentRequest();

        $unmodifiedVehicle = clone $vehicle;

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $isValid = $form->isValid();
            if ($isValid) {
                $this->entityManager->flush();
            }

            $cancel = $form->get('cancel');
            if ($isValid || ($cancel instanceof SubmitButton && $cancel->isClicked())) {
                return new RedirectResponse(
                    $this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $vehicle->getSurveyResponse()->getSurvey()->getId()]).
                    "#{$vehicle->getId()}");
            }
        }

        return $this->render($template, [
            'vehicle' => $unmodifiedVehicle,
            'form' => $form->createView(),
        ]);
    }
}