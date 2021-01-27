<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\Domestic\DaySummary;
use App\Form\Admin\DomesticSurvey\Edit\DaySummaryType;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/day-summaries")
 */
class DaySummaryController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_daysummary_';
    public const DELETE_ROUTE = self::ROUTE_PREFIX . 'delete';
    public const EDIT_ROUTE = self::ROUTE_PREFIX . 'edit';

    /**
     * @Route("/{summaryId}", name=self::EDIT_ROUTE)
     * @Entity("summary", expr="repository.find(summaryId)")
     */
    public function edit(DaySummary $summary, Request $request, EntityManagerInterface $entityManager)
    {
        $survey = $summary->getDay()->getResponse()->getSurvey();

        $form = $this->createForm(DaySummaryType::class, $summary);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            $redirectResponse = new RedirectResponse($this->generateUrl(SurveyController::VIEW_ROUTE, ['surveyId' => $survey->getId()]) . '#' . $summary->getId());

            $cancel = $form->get('cancel');
            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return $redirectResponse;
            }

            if ($form->isValid()) {
                $entityManager->flush();
                return $redirectResponse;
            }
        }

        return $this->render("admin/domestic/summary/edit.html.twig", [
            'survey' => $survey,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{summaryId}/delete", name=self::DELETE_ROUTE)
     * @Entity("summary", expr="repository.find(summaryId)")
     */
    public function delete(DaySummary $summary)
    {

    }
}