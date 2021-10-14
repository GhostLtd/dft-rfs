<?php


namespace App\Repository;


use App\Entity\Utility\MaintenanceWarning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Contracts\Translation\TranslatorInterface;

class MaintenanceWarningRepository extends ServiceEntityRepository
{
    private TranslatorInterface $translator;

    public function __construct(ManagerRegistry $registry, TranslatorInterface $translator)
    {
        parent::__construct($registry, MaintenanceWarning::class);
        $this->translator = $translator;
    }

    public function getNotificationBannerForFrontend($warningPeriodDateModifier = '+1 week')
    {
        /** @var MaintenanceWarning $warning */
        $warning = $this->createQueryBuilder('maintenanceWarning')
            ->andWhere('maintenanceWarning.start >= :now')
            ->andWhere('maintenanceWarning.start < :future')
            ->orderBy('maintenanceWarning.start', 'DESC')
            ->setMaxResults(1)
            ->setParameters([
                'now' => (new \DateTime())->modify('-15 minutes'),
                'future' => (new \DateTime())->modify($warningPeriodDateModifier)
            ])
            ->getQuery()
            ->getOneOrNullResult();
        if ($warning) {
            $timeFormat = $this->translator->trans('format.time.default');
            $dateFormat = $this->translator->trans('format.date.full-with-year');
            return new NotificationBanner(
                $this->translator->trans('maintenance-warning.banner.title'),
                $this->translator->trans('maintenance-warning.banner.heading'),
                $this->translator->trans('maintenance-warning.banner.content', [
                    'startTime' => $warning->getStart()->format($timeFormat),
                    'endTime' => $warning->getEnd()->format($timeFormat),
                    'date' => $warning->getStart()->format($dateFormat),
                ])
            );
        }
        return false;
    }
}