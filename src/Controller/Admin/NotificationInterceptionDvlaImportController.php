<?php

namespace App\Controller\Admin;

use App\Entity\NotificationInterception;
use App\Entity\NotificationInterceptionCompanyName;
use App\Form\Admin\LCNI\DvlaImportType;
use Doctrine\Bundle\DoctrineBundle\Middleware\BacktraceDebugDataHolder;
use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/notification-interception/dvla-import', name: 'admin_notification_interception_dvla_import')]
class NotificationInterceptionDvlaImportController extends AbstractController
{

    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
        protected string                 $appEnvironment,
    ) {}

    /**
     * This controller is prone to exhaust memory limits in dev environment, due to debug mode
     * add APP_DEBUG=false to env file, or enable the call to `resetDebugDataHolder`
     */
    #[Route(name: '')]
    public function import(Request $request): ?Response
    {
        $form = $this->createForm(DvlaImportType::class, null, [
            'cancel_url' => $this->generateUrl('admin_notification_interception_list'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filename = $this->preProcessUploadedDvlaSpreadsheetFile($form->get('upload')->getData()->getPathname());
            $this->clearExistingDvlaRecords();
            $this->batchImportDvlaDataFromTempFile($filename);

            // add notification banner
            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                new TranslatableMessage('common.notification.success', [], 'messages'),
                'notification-interception.dvla-import.confirmed-notification.heading',
                'notification-interception.dvla-import.confirmed-notification.content',
                ['style' => NotificationBanner::STYLE_SUCCESS],
                [],
                'admin'
            ));

            return $this->redirectToRoute('admin_notification_interception_list');
        }
        return $this->render('admin/notification_interception/dvla_import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function preProcessUploadedDvlaSpreadsheetFile(string $uploadedFilename): mixed
    {
        $spreadsheet = IOFactory::load($uploadedFilename);
        if ($spreadsheet->getSheetCount() !== 1) {
            throw new \RuntimeException('too many sheets');
        }

        $sheet = $spreadsheet->getActiveSheet();
        if (count($sheet->getColumnDimensions()) !== 2) {
            throw new \RuntimeException('expecting 2 columns');
        }

        $expectedRowTitles = $this->getRowValues($sheet->getRowIterator()->current());
        if ($expectedRowTitles !== ['A' => 'Operator Name', 'B' => 'Email address']) {
            throw new \RuntimeException('Column titles not as expected');
        }

        $outputFilename = tempnam(sys_get_temp_dir(), 'dvla_import');

        foreach ($sheet->getRowIterator(2) as $row) {
            ['A' => $companyName, 'B' => $email] = $this->getRowValues($row);
            file_put_contents($outputFilename, "$companyName\n$email\n", FILE_APPEND);
        }
        $spreadsheet->disconnectWorksheets();

        return $outputFilename;
    }

    protected function clearExistingDvlaRecords(): void
    {
        $dvlaOnlyIds = $this->entityManager->createQueryBuilder()
            ->select('ni.id')
            ->from(NotificationInterception::class, 'ni')
            ->where('ni.csrgtEmails IS NULL AND ni.irhsEmails IS NULL')
            ->getQuery()
            ->getSingleColumnResult();
        $this->entityManager->createQueryBuilder()
            ->delete(NotificationInterceptionCompanyName::class, 'cn')
            ->where('cn.notificationInterception in (:ids)')
            ->setParameter('ids', $dvlaOnlyIds)
            ->getQuery()
            ->execute();
        $this->entityManager->createQueryBuilder()
            ->delete(NotificationInterception::class, 'ni')
            ->where('ni.id in (:ids)')
            ->setParameter('ids', $dvlaOnlyIds)
            ->getQuery()
            ->execute();
        $this->entityManager->createQueryBuilder()
            ->update(NotificationInterception::class, 'ni')
            ->set('ni.dvlaEmails', 'NULL')
            ->getQuery()
            ->execute();
    }

    protected function batchImportDvlaDataFromTempFile(string $filename): int
    {
        $batchSize = 1000;

        $batch = [];
        $count = 0;
        $fileHandle = fopen($filename, 'r');
        while (($companyName = fgets($fileHandle)) !== false) {
            if (($email = fgets($fileHandle)) === false) {
                throw new \RuntimeException('unexpected file contents');
            }
            $companyName = trim($companyName);
            $email = trim($email);
            if (empty($companyName) || empty($email)) {
                continue;
            }

            // company name isn't unique, so we can't use it a key
            $batch[] = [$companyName, $email];
            $count++;

            if (count($batch) >= $batchSize) {
                $this->processImportBatch($batch);
                $batch = [];
            }
        }
        // process the last batch
        $this->processImportBatch($batch);

        return $count;
    }

    protected function processImportBatch(array $batch): void
    {
        $repo = $this->entityManager->getRepository(NotificationInterceptionCompanyName::class);
        $companyNames = array_map(fn($a) => $a[0], $batch);
        $existingRecordsByName = $repo->createQueryBuilder('cn', 'cn.name')
            ->select('cn, ni')
            ->leftJoin('cn.notificationInterception', 'ni')
            ->where('cn.name in (:companyNames)')
            ->setParameter('companyNames', $companyNames)
            ->getQuery()
            ->execute()
        ;
        $existingRecordsByName = array_change_key_case($existingRecordsByName, CASE_LOWER);

        foreach ($batch as [$companyName, $email]) {
            // we want to merge in the DVLA email address with existing records where the business name matches,
            // but we don't want to merge in business names with existing records where the email address matches
            // as this is a one way thing, and can't be undone the next time we import the DVLA data
            if ($existing = ($existingRecordsByName[strtolower($companyName)] ?? false)) {
                $existing->getNotificationInterception()->addDvlaEmail(strtolower($email));
            } else {
                $record = (new NotificationInterception())
                    ->addName($companyName)
                    ->setDvlaEmails(strtolower($email))
                ;
                $this->entityManager->persist($record);
                $existingRecordsByName[strtolower($companyName)] = $record->getNames()[0];
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
//        $this->resetDebugDataHolder();
    }

    protected function getRowValues(Row $row): array
    {
        return array_map(fn(Cell $c) => $c->getValue(), iterator_to_array($row->getCellIterator()));
    }

    protected function resetDebugDataHolder(): void
    {
        if ($this->appEnvironment === 'dev') {
            /** @var DebugMiddleware $debugMiddleware */
            $debugMiddleware = $this->entityManager->getConnection()->getConfiguration()->getMiddlewares()[1];
            $reflectionProperty = new ReflectionProperty(DebugMiddleware::class, 'debugDataHolder');
            /** @var BacktraceDebugDataHolder $debugDataHolder */
            $debugDataHolder = $reflectionProperty->getValue($debugMiddleware);
            $debugDataHolder->reset();
        }
    }
}
