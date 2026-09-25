<?php

namespace App\Utility\Reminder;

use App\Utility\Notification\NotificationType;

/**
 * The reminder selection for one family: due work, held-back surveys and reasons for sending nothing.
 */
final class DueReminders
{
    /**
     * @param list<NotificationType> $types the family's types, in send order (present even with nothing due)
     * @param list<DueItem> $items
     * @param list<HeldBack> $heldBack
     * @param list<string> $notes why a run would send nothing at all (e.g. weekend, unset date)
     */
    public function __construct(
        public readonly string $family,
        public readonly array $types,
        public readonly array $items,
        public readonly array $heldBack = [],
        public readonly array $notes = [],
    ) {}

    /**
     * Returns the due items for this reminder type, or an empty list.
     *
     * @return list<DueItem>
     */
    public function ofType(NotificationType $type): array
    {
        return array_values(array_filter($this->items, fn(DueItem $item) => $item->type === $type));
    }
}
