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
        $this->lock = $this->lockFactory->createLock('lcni-dvla-import', 60 * 2, false);
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
                new TranslatableMessage('common.notification.success', [], 'messages'),
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
        $this->dvlaImportData = $this->decoder->decode(file_get_contents($uploadedFilename), 'csv', [
            CsvEncoder::NO_HEADERS_KEY => true,
            CsvEncoder::OUTPUT_UTF8_BOM_KEY => true,
        ]);
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
            ['A' => $companyName, 'B' => $email] = $this->getRowValues($row);
            $this->dvlaImportData[] = [trim($companyName), strtolower(trim($email))];
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
        $this->entityManager->createQueryBuilder()
            ->delete(NotificationInterception::class, 'ni')
            ->where('ni.dvlaEmails IS NULL and ni.csrgtEmails is NULL and ni.irhsEmails is NULL');
    }

    protected function batchImportDvlaDataFromTempFile(): void
    {
        $batchSize = 1000;

        for ($i = 0; $i < ceil(count($this->dvlaImportData) / $batchSize) ; $i++) {
            $this->processImportBatch(array_slice($this->dvlaImportData, $i * $batchSize, $batchSize));
            $this->lock->refresh();
        }
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
