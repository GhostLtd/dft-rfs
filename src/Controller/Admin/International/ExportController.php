<?php

namespace App\Controller\Admin\International;

use App\Form\ConfirmationType;
use App\Utility\International\ExportHelper;
use App\Utility\International\DataExporter;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public const EXPORT_WEEK_ROUTE = self::ROUTE_PREFIX . 'week';
    public const DOWNLOAD_ROUTE = self::ROUTE_PREFIX . 'download';

    protected ExportHelper $exportHelper;

    public function __construct(ExportHelper $storageHelper)
    {
        $this->exportHelper = $storageHelper;
    }

    /**
     * @Route("", name=self::EXPORT_ROUTE)
     */
    public function list()
    {
        return $this->render('admin/international/export/list.html.twig', [
            'exports' => $this->exportHelper->getExportsExistingAndPossible(),
        ]);
    }

    /**
     * @Route("/{week}", name=self::EXPORT_WEEK_ROUTE, requirements={"week": "\d+"})
     */
    public function exportWeek(Request $request, string $week, DataExporter $dataExporter, TranslatorInterface $translator)
    {
        if ($this->objectExists($week)) {
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
                if ($dataExporter->uploadExportData($week)) {
                    return new RedirectResponse($this->generateUrl(self::SUCCESS_ROUTE, ['week' => $week]));
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
            'week' => $week,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{week}/success", name=self::SUCCESS_ROUTE, requirements={"week": "\d+"})
     */
    public function success(string $week)
    {
        if (!$this->objectExists($week)) {
            throw new NotFoundHttpException();
        }

        return $this->render('admin/international/export/success.html.twig', [
            'week' => $week,
        ]);
    }

    /**
     * @Route("/{week}/download", name=self::DOWNLOAD_ROUTE, requirements={"week": "\d+"})
     */
    public function download(string $week)
    {
        $signedUrl = $this->exportHelper->getSignedUrl($week);

        if (!$signedUrl) {
            throw new NotFoundHttpException();
        }

        return new RedirectResponse($signedUrl);
    }

    protected function objectExists(string $week)
    {
        return !!$this->exportHelper->getStorageObjectIfExists($week);
    }
}