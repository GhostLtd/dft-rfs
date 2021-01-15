<?php


namespace App\Controller\Admin\Domestic;

use App\Form\Admin\DomesticSurvey\ImportDvlaFileUploadType;
use App\Form\Admin\DomesticSurvey\ImportDvlaReviewDataType;
use App\Utility\DvlaImporter;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/dvla-import", name="admin_domestic_importdvla_")
 */
class ImportFromDvlaController extends AbstractController
{
    const SESSION_KEY = 'domestic-survey-import-data';

    /**
     * @Route("", name="index")
     * @Template()
     */
    public function index(Request $request, Session $session, DvlaImporter $dvlaImporter)
    {
        $form = $this->createForm(ImportDvlaFileUploadType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted())
        {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            $importData = $dvlaImporter->getDataAndOptions($form);

            if ($form->isValid()) {
                $session->set(self::SESSION_KEY, $importData);
                return $this->redirectToRoute('admin_domestic_importdvla_review');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/review", name="review")
     * @Template()
     */
    public function review(Request $request, Session $session, DvlaImporter $dvlaImporter, EntityManagerInterface $entityManager)
    {
        // grab the data from the session
        $data = $session->get(self::SESSION_KEY, false);
        if ($data === false) {
            return $this->redirectToRoute('admin_domestic_importdvla_index');
        }
        $form = $this->createForm(ImportDvlaReviewDataType::class, null, [
            'dvla_data' => $data['importData'],
            'dvla_filename' => $data['filename'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $surveys = [];
            $surveysSelected = array_intersect_key($data['importData'], $form->get('review_data')->getData());
            foreach ($surveysSelected as $surveyData) {
                $survey = $dvlaImporter->createSurvey($data['surveyOptions'], $surveyData);
                $entityManager->persist($survey);
                $surveys[] = $survey;
            }
            $entityManager->flush();
            $session->remove(self::SESSION_KEY);
            $session->getFlashBag()->add('summary', ['surveyOptions' => $data['surveyOptions'], 'surveys' => $surveys]);
            $session->getFlashBag()->add('notification',
                new NotificationBanner('Success', 'Surveys created', 'The surveys have been created', ['type' => 'success'])
            );

            return $this->redirectToRoute('admin_domestic_importdvla_summary');
        }

        // show it
        return array_merge($data, [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/summary", name="summary")
     * @Template()
     */
    public function summary(Session $session)
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