<?php

namespace App\Controller\Admin\International;

use App\Form\ConfirmationType;
use App\Utility\International\ExportHelper;
use App\Utility\International\DataExporter;
use DateTime;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/irhs/export", name="admin_international_export_")
 */
class ExportController extends AbstractController
{
    protected ExportHelper $exportHelper;

    public function __construct(ExportHelper $exportHelper)
    {
        $this->exportHelper = $exportHelper;
    }

    /**
     * @Route("", name="list")
     */
    public function list(): Response
    {
        return $this->render('admin/international/export/list.html.twig', [
            'exports' => $this->exportHelper->getPossibleYearsAndQuarters(true),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}", name="quarter", requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function exportQuarter(Request $request, string $year, string $quarter, DataExporter $dataExporter, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'Export',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('no');
            $export = $form->get('yes');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($this->generateUrl('admin_international_export_list'));
            } else if ($export instanceof SubmitButton && $export->isClicked()) {
                if ($filename = $dataExporter->generateExportData($year, $quarter)) {
                    $now = new DateTime();
                    return $this
                        ->file(new File($filename), "IRHS_export_{$year}_Q{$quarter}_{$now->format('Ymd_Hi')}.sql")
                        ->deleteFileAfterSend(true);
                } else {
                    $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                        $translator->trans('international.export-failed-notification.title', [], 'admin'),
                        $translator->trans('international.export-failed-notification.heading', [], 'admin'),
                        $translator->trans('international.export-failed-notification.content', [], 'admin')
                    ));
                    return new RedirectResponse($this->generateUrl('admin_international_export_list'));
                }
            }
        }

        return $this->render('admin/international/export/confirm.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
            'form' => $form->createView(),
        ]);
    }
}