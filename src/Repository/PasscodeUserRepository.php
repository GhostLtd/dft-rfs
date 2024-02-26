<?php

namespace App\Repository;

use App\Entity\PasscodeUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method PasscodeUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasscodeUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasscodeUser[]    findAll()
 * @method PasscodeUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasscodeUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasscodeUser::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    #[\Override]
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof PasscodeUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($user);
        $entityManager->flush();
    }

    public function lookupUserByIdentifier(string $userIdentifier): ?PasscodeUser
    {
        return $this->createQueryBuilder('u')
            ->select('u, d, i, p')
            ->leftJoin('u.domesticSurvey', 'd')
            ->leftJoin('u.internationalSurvey', 'i')
            ->leftJoin('u.preEnquiry', 'p')
            ->where('u.username = :username')
            ->setParameter('username', $userIdentifier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
