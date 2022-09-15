<?php


namespace App\Controller\Admin\PreEnquiry;


use App\Entity\PreEnquiry\PreEnquiry;
use App\Serializer\Encoder\SqlServerInsertEncoder;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/pre-enquiry/export", name="admin_preenquiry_export_")
 */
class ExportController extends AbstractController
{
    /**
     * @Route("", name="list")
     * @Template("admin/pre_enquiry/export/list.html.twig")
     */
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

    /**
     * @Route("/{year}-{month}", name="month",
     *     requirements={"year": "20\d{2}", "month": "(0[1-9]|1[0-2])"}
     * )
     */
    public function month(SerializerInterface $serializer, EntityManagerInterface $em, int $year, int $month): Response
    {
        // select pre-enquiries that were completed in given month
        $preEnquiries = $em
            ->getRepository(PreEnquiry::class)
            ->findForExportMonth($year, $month);

        $tmpFile = tempnam(sys_get_temp_dir(), 'rfs-preenquiry-export-');
        file_put_contents($tmpFile, $serializer->serialize($preEnquiries, 'sql-server-insert', [
            SqlServerInsertEncoder::TABLE_NAME_KEY => 'tbl_pre_enquiry',
            SqlServerInsertEncoder::FORCE_STRING_FIELDS => ["contactPhone", "addressLine1", "addressLine2", "addressLine3", "addressLine4", "addressLine5", "addressLine6", "addressPostcode", "businessSize"],
        ]));

        $now = new DateTime();
        return $this->file($tmpFile, "PREENQUIRY_export_{$year}_{$month}_{$now->format('Ymd_Hi')}.sql")
            ->deleteFileAfterSend(true);
    }
}