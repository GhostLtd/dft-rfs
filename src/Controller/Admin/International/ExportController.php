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
 * @Route("/irhs/export")
 */
class ExportController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_international_export_';
    public const EXPORT_ROUTE = self::ROUTE_PREFIX . 'list';
    public const SUCCESS_ROUTE = self::ROUTE_PREFIX . 'success';
    public const EXPORT_QUARTER_ROUTE = self::ROUTE_PREFIX . 'quarter';
    public const DOWNLOAD_ROUTE = self::ROUTE_PREFIX . 'download';

    protected ExportHelper $exportHelper;

    public function __construct(ExportHelper $exportHelper)
    {
        $this->exportHelper = $exportHelper;
    }

    /**
     * @Route("", name=self::EXPORT_ROUTE)
     */
    public function list(): Response
    {
        return $this->render('admin/international/export/list.html.twig', [
            'exports' => $this->exportHelper->getExportsExistingAndPossible(),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}", name=self::EXPORT_QUARTER_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function exportQuarter(Request $request, string $year, string $quarter, DataExporter $dataExporter, TranslatorInterface $translator): Response
    {
        if ($this->objectExists($year, $quarter)) {
            throw new NotFoundHttpException();
        }

        $form = $this->createForm(ConfirmationType::class, null, [
            'yes_label' => 'Export',
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);

            $cancel = $form->get('no');
            $export = $form->get('yes');

            if ($cancel instanceof SubmitButton && $cancel->isClicked()) {
                return new RedirectResponse($this->generateUrl(self::EXPORT_ROUTE));
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
                    return new RedirectResponse($this->generateUrl(self::EXPORT_ROUTE));
                }
            }
        }

        return $this->render('admin/international/export/confirm.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}/success", name=self::SUCCESS_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function success(string $year, string $quarter): Response
    {
        if (!$this->objectExists($year, $quarter)) {
            throw new NotFoundHttpException();
        }

        return $this->render('admin/international/export/success.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
        ]);
    }

    /**
     * @Route("/{year}/{quarter}/download", name=self::DOWNLOAD_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function download(string $year, string $quarter): Response
    {
        $signedUrl = $this->exportHelper->getSignedUrl($year, $quarter);

        if (!$signedUrl) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($signedUrl);
    }

    protected function objectExists(string $year, string $quarter): bool
    {
        return !!$this->exportHelper->getStorageObjectIfExists($year, $quarter);
    }
}