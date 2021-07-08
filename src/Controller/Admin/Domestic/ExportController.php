<?php

namespace App\Controller\Admin\Domestic;

use App\Form\ConfirmationType;
use App\Utility\Domestic\DataExporter;
use App\Utility\Domestic\ExportHelper;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/csrgt/export")
 */
class ExportController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_export_';
    public const EXPORT_ROUTE = self::ROUTE_PREFIX . 'list';
    public const SUCCESS_ROUTE = self::ROUTE_PREFIX . 'success';
    public const EXPORT_QUARTER_ROUTE = self::ROUTE_PREFIX . 'quarter';
    public const DOWNLOAD_ROUTE = self::ROUTE_PREFIX . 'download';

    protected ExportHelper $storageHelper;

    public function __construct(ExportHelper $storageHelper)
    {
        $this->storageHelper = $storageHelper;
    }

    /**
     * @Route("", name=self::EXPORT_ROUTE)
     */
    public function list()
    {
        return $this->render('admin/domestic/export/list.html.twig', [
            'exports' => $this->storageHelper->getExportsExistingAndPossible(),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}", name=self::EXPORT_QUARTER_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function exportQuarter(Request $request, string $year, string $quarter, DataExporter $exporter, TranslatorInterface $translator)
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
                if ($exporter->uploadExportData($year, $quarter)) {
                    return new RedirectResponse($this->generateUrl(self::SUCCESS_ROUTE, ['quarter' => $quarter, 'year' => $year]));
                } else {
                    $this->addFlash(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                        $translator->trans('domestic.export-failed-notification.title', [], 'admin'),
                        $translator->trans('domestic.export-failed-notification.heading', [], 'admin'),
                        $translator->trans('domestic.export-failed-notification.content', [], 'admin')
                    ));
                    return new RedirectResponse($this->generateUrl(self::EXPORT_ROUTE));
                }
            }
        }

        return $this->render('admin/domestic/export/confirm.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{year}/{quarter}/success", name=self::SUCCESS_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function success(string $year, string $quarter)
    {
        if (!$this->objectExists($year, $quarter)) {
            throw new NotFoundHttpException();
        }

        return $this->render('admin/domestic/export/success.html.twig', [
            'quarter' => $quarter,
            'year' => $year,
        ]);
    }

    /**
     * @Route("/{year}/{quarter}/download", name=self::DOWNLOAD_ROUTE, requirements={"year": "\d{4}", "quarter": "[1-4]{1}"})
     */
    public function download(string $year, string $quarter)
    {
        $signedUrl = $this->storageHelper->getSignedUrl($year, $quarter);

        if (!$signedUrl) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($signedUrl);
    }

    protected function objectExists(string $year, string $quarter)
    {
        return !!$this->storageHelper->getStorageObjectIfExists($year, $quarter);
    }
}