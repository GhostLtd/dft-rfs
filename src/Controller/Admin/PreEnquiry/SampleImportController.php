<?php

namespace App\Controller\Admin\PreEnquiry;

use App\Form\Admin\InternationalSurvey\ImportSampleFileUploadType;
use App\Form\Admin\InternationalSurvey\ImportSampleReviewDataType;
use App\Utility\PreEnquiry\SampleImporter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/pre-enquiry/sample-import', name: 'admin_preenquiry_sampleimport_')]
class SampleImportController extends AbstractController
{
    public const SESSION_KEY = 'preenquiry-survey-import-data';

    #[Route(path: '', name: 'start')]
    #[Template('admin/pre_enquiry/sample_import/index.html.twig')]
    public function index(Request $request, SampleImporter $sampleImporter): RedirectResponse|array
    {
        $form = $this->createForm(ImportSampleFileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            $importDataAndOptions = $sampleImporter->getSurveys($form);

            if ($form->isValid()) {
                $session = $request->getSession();
                $session->set(self::SESSION_KEY, $importDataAndOptions);
                return $this->redirectToRoute('admin_preenquiry_sampleimport_review');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    #[Route(path: '/review', name: 'review')]
    #[Template('admin/pre_enquiry/sample_import/review.html.twig')]
    public function review(Request $request, Session $session, EntityManagerInterface $entityManager): RedirectResponse|array
    {
        // grab the data from the session
        $data = $session->get(self::SESSION_KEY, false);
        if ($data === false) {
            return $this->redirectToRoute('admin_preenquiry_sampleimport_start');
        }
        $form = $this->createForm(ImportSampleReviewDataType::class, null, [
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
            $session->getFlashBag()->add('summary', ['surveys' => $surveysSelected]);
            $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE,
                new NotificationBanner('Success', 'Pre-Enquiries created', 'The surveys have been created', ['style' => NotificationBanner::STYLE_SUCCESS])
            );

            return $this->redirectToRoute('admin_preenquiry_sampleimport_summary');
        }

        // show it
        return array_merge($data, [
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/summary', name: 'summary')]
    #[Template('admin/pre_enquiry/sample_import/summary.html.twig')]
    public function summary(Session $session): RedirectResponse|array
    {
        $data = $session->getFlashBag()->get('summary', []);
        if (!empty($data)) {
            $data = reset($data);
        } else {
            return $this->redirectToRoute('admin_preenquiry_sampleimport_start');
        }
        return $data;
    }
}
