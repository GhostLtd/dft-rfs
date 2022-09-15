<?php

namespace App\Controller\Admin\Domestic;

use App\Form\ConfirmationType;
use App\Utility\Domestic\DataExporter;
use App\Utility\Domestic\ExportHelper;
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
 * @Route("/csrgt/export", name="admin_domestic_export_")
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
        return $this->render('admin/domestic/export/list.html.twig', [
            'exports' => $this->exportHelper->getPossibleYearsAndQuarters(true),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}", name="quarter", requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function exportQuarter(Request $request, string $year, string $quarter, DataExporter $exporter, TranslatorInterface $translator): Response
    {
        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'Export',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('no');
            $export = $form->get('yes');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($this->generateUrl('admin_domestic_export_list'));
            } else if ($export instanceof SubmitButton && $export->isClicked()) {
                if ($filename = $exporter->generateExportData($year, $quarter)) {
                    $now = new DateTime();
                    return $this
                        ->file(new File($filename), "CSRGT_export_{$year}_Q{$quarter}_{$now->format('Ymd_Hi')}.sql")
                        ->deleteFileAfterSend(true);
                } else {
                    $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                        $translator->trans('domestic.export-failed-notification.title', [], 'admin'),
                        $translator->trans('domestic.export-failed-notification.heading', [], 'admin'),
                        $translator->trans('domestic.export-failed-notification.content', [], 'admin')
                    ));
                    return new RedirectResponse($this->generateUrl('admin_domestic_export_list'));
                }
            }
        }

        return $this->render('admin/domestic/export/confirm.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
            'form' => $form->createView(),
        ]);
    }
}