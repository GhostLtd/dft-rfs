<?php

namespace App\Command;

use App\Entity\International\SamplingGroup;
use App\Repository\International\SamplingGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnexpectedResultException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('rfs:fixtures:create-sampling-groups')]
class FixturesCreateSamplingGroupsCommand extends Command
{
    public const SIZE_GROUP_TO_NUMBER_MAPPING = [
        1 => 25,
        3 => 25,
        5 => 25,
        12 => 50,
        24 => 100,
    ];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected SamplingGroupRepository $samplingGroupRepository
    )
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Creates sampling groups')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $numberOfExistingGroups = $this->samplingGroupRepository
                ->createQueryBuilder('s')
                ->select('count(s.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (UnexpectedResultException) {
            $io->error('Unable to fetch number of existing groups');
            return 1;
        }

        if ($numberOfExistingGroups > 0) {
            $io->error('Sampling groups already exist');
            return 2;
        }

        foreach(self::SIZE_GROUP_TO_NUMBER_MAPPING as $sizeGroup => $numberOfGroups) {
            for($number=1; $number <= $numberOfGroups; $number++) {
                $samplingGroup = (new SamplingGroup())
                    ->setSizeGroup($sizeGroup)
                    ->setNumber($number);

                $this->entityManager->persist($samplingGroup);
            }
        }

        $this->entityManager->flush();

        $io->success('Sampling groups created');

        return 0;
    }
}
