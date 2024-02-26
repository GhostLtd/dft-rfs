<?php

namespace App\Controller\Admin\Domestic;

use App\Form\Admin\DomesticSurvey\ImportDvlaFileUploadType;
use App\Form\Admin\DomesticSurvey\ImportDvlaReviewDataType;
use App\Utility\Domestic\DvlaImporter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/csrgt/dvla-import', name: 'admin_domestic_importdvla_')]
class ImportFromDvlaController extends AbstractController
{
    public const SESSION_KEY = 'domestic-survey-import-data';

    #[Route(path: '', name: 'index')]
    #[Template('admin/domestic/import_from_dvla/index.html.twig')]
    public function index(Request $request, Session $session, DvlaImporter $dvlaImporter): RedirectResponse|array
    {
        $form = $this->createForm(ImportDvlaFileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            $allowHistoricalDate = $form->get('survey_options')->get('allowHistoricalDate')->getData();
            $importDataAndOptions = $dvlaImporter->getSurveys($form, $allowHistoricalDate);

            if ($form->isValid()) {
                $session->set(self::SESSION_KEY, $importDataAndOptions);
                return $this->redirectToRoute('admin_domestic_importdvla_review');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    #[Route(path: '/review', name: 'review')]
    #[Template('admin/domestic/import_from_dvla/review.html.twig')]
    public function review(Request $request, Session $session, EntityManagerInterface $entityManager): RedirectResponse|array
    {
        // grab the data from the session
        $data = $session->get(self::SESSION_KEY, false);
        if ($data === false) {
            return $this->redirectToRoute('admin_domestic_importdvla_index');
        }
        $form = $this->createForm(ImportDvlaReviewDataType::class, null, [
            'surveys' => $data['surveys'],
            'uploaded_filename' => $data['filename'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $surveysSelected = array_intersect_key($data['surveys'], $form->get('review_data')->getData());
            foreach ($surveysSelected as $survey) {
                $entityManager->persist($survey);
            }
            $entityManager->flush();

            $session->remove(self::SESSION_KEY);
            $session->getFlashBag()->add('summary', ['surveyOptions' => $data['surveyOptions'], 'surveys' => $surveysSelected]);
            $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE,
                new NotificationBanner('Success', 'Surveys created', 'The surveys have been created', ['style' => NotificationBanner::STYLE_SUCCESS])
            );

            return $this->redirectToRoute('admin_domestic_importdvla_summary');
        }

        // show it
        return array_merge($data, [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/summary', name: 'summary')]
    #[Template('admin/domestic/import_from_dvla/summary.html.twig')]
    public function summary(Session $session): RedirectResponse|array
    {
        $data = $session->getFlashBag()->get('summary', []);
        if (!empty($data)) {
            $data = reset($data);
        } else {
            return $this->redirectToRoute('admin_domestic_importdvla_index');
        }
        return $data;
    }
}
