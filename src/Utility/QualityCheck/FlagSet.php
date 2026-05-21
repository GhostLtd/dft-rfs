<?php

namespace App\Utility\QualityCheck;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;

/**
 * @implements \IteratorAggregate<int, QualityFlag>
 */
class FlagSet implements \Countable, \IteratorAggregate
{
    /** @var QualityFlag[] */
    private array $flags;

    /** @param QualityFlag[] $flags */
    public function __construct(array $flags = [])
    {
        $this->flags = array_values($flags);
    }

    public function add(QualityFlag $flag): void
    {
        $this->flags[] = $flag;
    }

    /** @return QualityFlag[] */
    public function all(): array
    {
        return $this->flags;
    }

    public function count(): int
    {
        return count($this->flags);
    }

    public function isEmpty(): bool
    {
        return $this->flags === [];
    }

    /**
     * Order-independent fingerprint of the flag set, used to detect when an edit genuinely changes
     * the flags. Empty set returns ''.
     */
    public function signature(): string
    {
        $parts = array_map(
            fn(QualityFlag $f) => $f->subjectType->value . '|' . $f->subjectId . '|' . $f->reasonCode . '|' . json_encode($f->payload),
            $this->flags,
        );
        sort($parts);

        return $parts === [] ? '' : md5(implode("\n", $parts));
    }

    public function forSubject(SubjectType $type, string $id): self
    {
        return new self(array_filter(
            $this->flags,
            fn(QualityFlag $f) => $f->subjectType === $type && $f->subjectId === $id,
        ));
    }

    /** Flags pinned to the response itself (excluding day/stop-level flags). */
    public function forResponse(): self
    {
        return new self(array_filter(
            $this->flags,
            fn(QualityFlag $f) => $f->subjectType === SubjectType::RESPONSE,
        ));
    }

    public function forDay(Day $day): self
    {
        return $this->forSubject(SubjectType::DAY, $day->getId());
    }

    public function forStop(DayStop $stop): self
    {
        return $this->forSubject(SubjectType::STOP, $stop->getId());
    }

    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->flags);
    }
}
