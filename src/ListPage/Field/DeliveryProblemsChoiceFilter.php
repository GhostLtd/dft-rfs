<?php

namespace App\ListPage\Field;

use App\Entity\NotifyCallback;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\Field\FilterableInterface;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;

/**
 * Filters a survey list by delivery problems (see NotifyApiResponse::hasDeliveryProblem()):
 *
 * - send-time: a response that finally failed (Notify rejected it, or a failure Messenger won't retry)
 * - after sending: a fatal Notify callback (permanent/technical failure, returned letter)
 *
 * each optionally limited to problems no admin has marked seen.
 */
class DeliveryProblemsChoiceFilter extends Simple implements FilterableInterface
{
    public const string CHOICE_ANY = 'any';
    public const string CHOICE_ANY_UNSEEN = 'any-unseen';
    public const string CHOICE_SEND_TIME = 'send-time';
    public const string CHOICE_SEND_TIME_UNSEEN = 'send-time-unseen';
    public const string CHOICE_AFTER_SENDING = 'after-sending';
    public const string CHOICE_AFTER_SENDING_UNSEEN = 'after-sending-unseen';

    // MySQL's ->> needs a literal path (not a parameter); the key contains dots, so it's quoted
    private const string STATUS_PATH = '$."symfony_messenger_bus.govuk_notify_status"';

    /**
     * @param class-string $notificationClass the survey type's notification entity (with a "survey" association)
     */
    public function __construct(
        string                  $label,
        private readonly string $notificationClass,
        private readonly string $surveyAlias,
        private array           $formOptions = [],
        array                   $cellOptions = [],
    )
    {
        parent::__construct($label, null, $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            'expanded' => false,
            'placeholder' => $this->label,
            'choices' => [
                'Any' => self::CHOICE_ANY,
                'Any (unseen)' => self::CHOICE_ANY_UNSEEN,
                'Send-time' => self::CHOICE_SEND_TIME,
                'Send-time (unseen)' => self::CHOICE_SEND_TIME_UNSEEN,
                'After sending' => self::CHOICE_AFTER_SENDING,
                'After sending (unseen)' => self::CHOICE_AFTER_SENDING_UNSEEN,
            ],
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, string $formData): QueryBuilder
    {
        $sendTime = "GHOST_JSON_GET(dp_r.data, '".self::STATUS_PATH."') = :dp_failed";
        $afterSending = 'EXISTS (SELECT dp_c.id FROM '.NotifyCallback::class.' dp_c WHERE dp_c.response = dp_r AND dp_c.status IN (:dp_fatal))';

        [$condition, $unseen] = match ($formData) {
            self::CHOICE_ANY => ["({$sendTime} OR {$afterSending})", false],
            self::CHOICE_ANY_UNSEEN => ["({$sendTime} OR {$afterSending})", true],
            self::CHOICE_SEND_TIME => [$sendTime, false],
            self::CHOICE_SEND_TIME_UNSEEN => [$sendTime, true],
            self::CHOICE_AFTER_SENDING => [$afterSending, false],
            self::CHOICE_AFTER_SENDING_UNSEEN => [$afterSending, true],
            default => [null, false],
        };

        if ($condition === null) {
            return $queryBuilder;
        }

        // The unseen test goes first: after the nested EXISTS, it trips up the Paginator's output walker (used by
        // list pages with an ORDER BY), which then selects columns of the subquery's alias (DeliveryProblemsListTest)
        if ($unseen) {
            $condition = "dp_r.problemSeenAt IS NULL AND {$condition}";
        }

        $queryBuilder->andWhere("EXISTS (SELECT dp_n.id FROM {$this->notificationClass} dp_n JOIN dp_n.apiResponses dp_r WHERE dp_n.survey = {$this->surveyAlias} AND {$condition})");

        if (str_contains($condition, ':dp_failed')) {
            $queryBuilder->setParameter('dp_failed', Reference::STATUS_FAILED);
        }
        if (str_contains($condition, ':dp_fatal')) {
            $queryBuilder->setParameter('dp_fatal', NotifyCallback::FATAL_STATUSES);
        }

        return $queryBuilder;
    }
}
