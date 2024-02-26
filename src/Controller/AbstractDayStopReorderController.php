<?php

namespace App\Controller;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\Survey;
use App\Form\ConfirmationType;
use App\Utility\ReorderUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractDayStopReorderController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    abstract protected function getRedirectResponse(Day $day): RedirectResponse;
    abstract protected function getTemplate(): string;

    protected function getDay(Survey $survey, string $dayNumber)
    {
        $dayRepository = $this->entityManager->getRepository(Day::class);

        try {
            return $dayRepository->getBySurveyAndDayNumber($survey, intval($dayNumber));
        }
        catch(NonUniqueResultException) {}

        throw new NotFoundHttpException();
    }

    protected function reorder(Request $request, Day $day): Response
    {
        /** @var DayStop[] $stops */
        $stops = array_values($day->getStops()->toArray());

        $mappingParam = $request->query->get('mapping', null);

        /** @var DayStop[] $sortedStops */
        $sortedStops = ReorderUtils::getSortedItems($stops, $mappingParam);

        $mapping = array_map(fn(DayStop $dayStop) => $dayStop->getNumber(), $sortedStops);

        foreach($mapping as $i => $newPosition) {
            $stops[$newPosition - 1]->setNumber($i + 1);
        }

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'domestic.day-stop.re-order.save',
            'no_label' => 'common.actions.cancel',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            if ($form->isSubmitted()) {
                $cancel = $form->get('no');
                if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                    return $this->getRedirectResponse($day);
                }

                if ($form->isValid()) {
                    $this->entityManager->flush();
                    return $this->getRedirectResponse($day);
                }
            }
        }

        return $this->render($this->getTemplate(), [
            'mapping' => $mapping,
            'day' => $day,
            'survey' => $day->getResponse()->getSurvey(),
            'sortedStops' => $sortedStops,
            'form' => $form,
        ]);
    }
}