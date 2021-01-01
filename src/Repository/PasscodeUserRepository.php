<?php

namespace App\Repository;

use App\Entity\PasscodeUser;
use App\Utility\PasscodeGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method PasscodeUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method PasscodeUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method PasscodeUser[]    findAll()
 * @method PasscodeUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PasscodeUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PasscodeGenerator
     */
    private $passcodeGenerator;
    private $appEnvironment;

    public function __construct(ManagerRegistry $registry, PasscodeGenerator $passcodeGenerator, $appEnvironment)
    {
        parent::__construct($registry, PasscodeUser::class);
        $this->registry = $registry;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->appEnvironment = $appEnvironment;
    }

    /**
     * @return PasscodeUser
     */
    public function createNewPasscodeUser()
    {
        return (new PasscodeUser())
            ->setUsername($this->passcodeGenerator->generatePasscode())
            ->setPlainPassword($this->appEnvironment === 'dev' ? 'dev' : $this->passcodeGenerator->generatePasscode())
            ;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof PasscodeUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // /**
    //  * @return PasscodeUser[] Returns an array of PasscodeUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PasscodeUser
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
