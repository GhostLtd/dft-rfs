<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\Survey;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use App\Serializer\Normalizer\RoRo\RoRoNormalizer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/roro/export', name: 'admin_roro_export_')]
class ExportController extends AbstractController
{
    #[Route(path: '', name: 'list')]
    #[Template('admin/roro/export/list.html.twig')]
    public function list(): array
    {
        // list last 12 months from last month
        $months = [];
        foreach (range(1, 12) as $m) {
            $months[] = (new \DateTime('first day of this month'))->modify("-{$m} months");
        }

        return [
            'months' => $months,
        ];
    }

    #[Route(path: '/{year}-{month}', name: 'month', requirements: ['year' => '20\d{2}', 'month' => '(0[1-9]|1[0-2])'])]
    public function month(SerializerInterface $serializer, EntityManagerInterface $em, int $year, int $month): Response
    {
        // select surveys that were completed in given month
        $surveys = $em
            ->getRepository(Survey::class)
            ->findForExportMonth($year, $month);

        $tmpFile = tempnam(sys_get_temp_dir(), 'rfs-roro-export-');
        file_put_contents($tmpFile, $serializer->serialize($surveys, 'sql-server-insert', [
            RoRoNormalizer::CONTEXT_KEY => true,
            SqlServerInsertEncoder::TABLE_NAME_KEY => 'tbl_roro',
            SqlServerInsertEncoder::FORCE_STRING_FIELDS => [
                'RoRoNo',
                'OperatorId',
                'UKPort',
                'ForeignPort',
                'DataEntryMethod',
            ],
        ]));

        $now = new DateTime();
        return $this->file($tmpFile, "RORO_export_{$year}_{$month}_{$now->format('Ymd_Hi')}.sql")
            ->deleteFileAfterSend(true);
    }
}
