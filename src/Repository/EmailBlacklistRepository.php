<?php

namespace App\Repository;

use App\Entity\EmailBlacklist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailBlacklist>
 *
 * @method EmailBlacklist|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailBlacklist|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailBlacklist[]    findAll()
 * @method EmailBlacklist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailBlacklistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailBlacklist::class);
    }

    public function add(EmailBlacklist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailBlacklist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function isBlacklisted(string $email): bool
    {
        return $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('LOWER(b.email) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * @param string[] $emails
     * @return string[]
     */
    public function filterBlacklistedEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $normalizedEmails = array_map(fn(string $email) => strtolower(trim($email)), $emails);

        $blacklisted = $this->createQueryBuilder('b')
            ->select('LOWER(b.email)')
            ->where('LOWER(b.email) IN (:emails)')
            ->setParameter('emails', $normalizedEmails)
            ->getQuery()
            ->getSingleColumnResult();

        $blacklistedSet = array_flip($blacklisted);

        return array_values(array_filter($emails, function(string $email) use ($blacklistedSet) {
            return !isset($blacklistedSet[strtolower(trim($email))]);
        }));
    }

    /**
     * @param string[] $emails
     * @return string[] Returns normalized (lowercase, trimmed) blacklisted emails
     */
    public function getBlacklistedFromList(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }

        $normalizedEmails = array_map(fn(string $email) => strtolower(trim($email)), $emails);
        $uniqueNormalized = array_unique($normalizedEmails);

        $blacklisted = $this->createQueryBuilder('b')
            ->select('LOWER(b.email)')
            ->where('LOWER(b.email) IN (:emails)')
            ->setParameter('emails', $uniqueNormalized)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values($blacklisted);
    }
}
