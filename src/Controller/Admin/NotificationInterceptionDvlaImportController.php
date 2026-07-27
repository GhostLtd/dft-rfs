<?php

namespace App\Controller\Admin;

use App\Entity\NotificationInterception;
use App\Entity\NotificationInterceptionCompanyName;
use App\Form\Admin\LCNI\DvlaImportType;
use App\Utility\LcniDisplayService;
use Doctrine\Bundle\DoctrineBundle\Middleware\BacktraceDebugDataHolder;
use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use ReflectionProperty;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\LockedHttpException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(path: '/notification-interception/dvla-import', name: 'admin_notification_interception_dvla_import')]
class NotificationInterceptionDvlaImportController extends AbstractController
{
    protected array $dvlaImportData = [];

    protected LockInterface $lock;

    public function __construct(
        protected RequestStack           $requestStack,
        protected EntityManagerInterface $entityManager,
        protected string                 $appEnvironment,
        protected DecoderInterface       $decoder,
        protected LockFactory            $lockFactory,
    ) {}

    /**
     * This controller is prone to exhaust memory limits in dev environment, due to debug mode
     * add APP_DEBUG=false to env file, or enable the call to `resetDebugDataHolder`
     */
    #[Route(name: '')]
    public function import(Request $request): ?Response
    {
        $this->lock = $this->lockFactory->createLock(LcniDisplayService::DVLA_IMPORT_LOCK_NAME, 60 * 2, false);
        if (!$this->lock->acquire()) {
            throw new LockedHttpException("Could not acquire lock");
        }

        $form = $this->createForm(DvlaImportType::class, null, [
            'cancel_url' => $this->generateUrl('admin_notification_interception_list'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('upload')->getData();
            match($file->getMimeType()) {
                'text/csv' => $this->preProcessUploadedDvlaSpreadsheetFile($file->getPathname()),
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => $this->preProcessUploadedDvlaSpreadsheetFilePhpOffice($file->getPathname()),
                default => throw new \RuntimeException('Unexpected file type'),
            };

            $this->clearExistingDvlaRecords();
            $this->batchImportDvlaDataFromTempFile();
            $this->clearEmptyRecords();
            $this->lock->release();

            // add notification banner
            /** @var FlashBagAwareSessionInterface $session */
            $session = $this->requestStack->getSession();
            $session->getFlashBag()->add(NotificationBanner::FLASH_BAG_TYPE, new NotificationBanner(
                new TranslatableMessage('notification.success', [], 'messages'),
                'notification-interception.dvla-import.confirmed-notification.heading',
                'notification-interception.dvla-import.confirmed-notification.content',
                ['style' => NotificationBanner::STYLE_SUCCESS],
                [],
                'admin'
            ));
            return $this->redirectToRoute('admin_notification_interception_list');
        }

        $this->lock->release();
        return $this->render('admin/notification_interception/dvla_import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function preProcessUploadedDvlaSpreadsheetFile(string $uploadedFilename): void
    {
        // Strip the UTF-8 BOM the DVLA files carry; the CSV decoder does not
        $contents = preg_replace('/^\x{FEFF}/u', '', file_get_contents($uploadedFilename));

        $rows = $this->decoder->decode($contents, 'csv', [
            CsvEncoder::NO_HEADERS_KEY => true,
        ]);

        // Same header validation and normalisation as the XLSX path, so both
        // file types produce identical import data
        $headerRow = array_shift($rows);
        if ($headerRow !== ['Operator Name', 'Email address']) {
            throw new \RuntimeException('Column titles not as expected');
        }

        foreach ($rows as $row) {
            $companyName = mb_trim($row[0] ?? '');
            $email = strtolower(mb_trim($row[1] ?? ''));
            if ($companyName === '' && $email === '') {
                continue;
            }
            $this->dvlaImportData[] = [$companyName, $email];
        }
    }
    protected function preProcessUploadedDvlaSpreadsheetFilePhpOffice(string $uploadedFilename): void
    {
        $reader = IOFactory::createReaderForFile($uploadedFilename)
            ->setReadDataOnly(true)
            ->setReadEmptyCells(false)
        ;

        $spreadsheet = $reader->load($uploadedFilename);
        $sheet = $spreadsheet->getSheet(0);

        $expectedRowTitles = $this->getRowValues($sheet->getRowIterator()->current());
        if ($expectedRowTitles !== ['A' => 'Operator Name', 'B' => 'Email address']) {
            throw new \RuntimeException('Column titles not as expected');
        }

        foreach ($sheet->getRowIterator(2) as $row) {
            $values = $this->getRowValues($row);
            $companyName = mb_trim((string) ($values['A'] ?? ''));
            $email = strtolower(mb_trim((string) ($values['B'] ?? '')));
            if ($companyName === '' && $email === '') {
                continue;
            }
            $this->dvlaImportData[] = [$companyName, $email];
        }
        $spreadsheet->disconnectWorksheets();
    }

    protected function clearExistingDvlaRecords(): void
    {
        $this->entityManager->createQueryBuilder()
            ->update(NotificationInterception::class, 'ni')
            ->set('ni.dvlaEmails', 'NULL')
            ->getQuery()
            ->execute();
    }

    protected function clearEmptyRecords(): void
    {
        // Can't use a simple DQL delete because the FK to notification_interception_company_name
        // has no ON DELETE CASCADE, so the child rows must be removed first.
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(<<<SQL
            DELETE cn FROM notification_interception_company_name cn
            INNER JOIN notification_interception ni ON cn.notification_interception_id = ni.id
            WHERE ni.dvla_emails IS NULL AND ni.csrgt_emails IS NULL AND ni.irhs_emails IS NULL
        SQL);
        $connection->executeStatement(<<<SQL
            DELETE FROM notification_interception
            WHERE dvla_emails IS NULL AND csrgt_emails IS NULL AND irhs_emails IS NULL
        SQL);
    }

    protected function batchImportDvlaDataFromTempFile(): void
    {
        $this->deduplicateImportData();
        $batchSize = 1000;

        for ($i = 0; $i < ceil(count($this->dvlaImportData) / $batchSize) ; $i++) {
            $this->processImportBatch(array_slice($this->dvlaImportData, $i * $batchSize, $batchSize));
            $this->lock->refresh();
        }
    }

    protected function deduplicateImportData(): void
    {
        $seen = [];
        $deduplicated = [];
        foreach ($this->dvlaImportData as $i => [$companyName, $email]) {
            unset($this->dvlaImportData[$i]);
            $key = mb_strtolower($companyName);
            if (isset($seen[$key])) {
                // Merge email into the existing entry
                $idx = $seen[$key];
                $existingEmails = array_filter(explode(',', $deduplicated[$idx][1]));
                if (!in_array($email, $existingEmails)) {
                    $existingEmails[] = $email;
                    $deduplicated[$idx][1] = implode(',', $existingEmails);
                }
            } else {
                $seen[$key] = count($deduplicated);
                $deduplicated[] = [$companyName, $email];
            }
        }
        $this->dvlaImportData = $deduplicated;
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
        $existingRecordsByName = array_combine(
            array_map(fn($k) => mb_strtolower($k), array_keys($existingRecordsByName)),
            array_values($existingRecordsByName),
        );

        foreach ($batch as [$companyName, $emails]) {
            // we want to merge in the DVLA email address with existing records where the business name matches,
            // but we don't want to merge in business names with existing records where the email address matches
            // as this is a one way thing, and can't be undone the next time we import the DVLA data
            $key = mb_strtolower($companyName);
            if ($existing = ($existingRecordsByName[$key] ?? false)) {
                // $emails may be comma-delimited due to the deduplication step
                foreach (explode(',', $emails) as $email) {
                    $existing->getNotificationInterception()->addDvlaEmail(strtolower($email));
                }
            } else {
                $record = (new NotificationInterception())
                    ->addName($companyName)
                    ->setDvlaEmails($emails)
                ;
                $this->entityManager->persist($record);
                $existingRecordsByName[$key] = $record->getNames()[0];
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
//        $this->resetDebugDataHolder();
    }

    protected function getRowValues(Row $row): array
    {
        return array_map(fn(Cell $c) => $c->getValue(), iterator_to_array($row->getCellIterator(iterateOnlyExistingCells: true)));
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
