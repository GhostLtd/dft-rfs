<?php


namespace App\ListPage\Domestic\Field;


use App\Form\Admin\DomesticSurvey\SurveyListField\WeekYearFilterType;
use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\QueryBuilder;

class WeekYearFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, string $propertyPath, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            // no default options
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return WeekYearFilterType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if (!isset($formData['year']) || !is_numeric($formData['year'])) {
            return $queryBuilder;
        }

        if (isset($formData['week']) && is_numeric($formData['week'])) {
            $start = WeekNumberHelper::getDateForYearAndWeek($formData['year'], $formData['week']);
            $end = WeekNumberHelper::getDateForYearAndWeek($formData['year'], $formData['week']);

            if (!$end) {
                // Impossible year/week combo.
                // Generally caused be "no week 53 in chosen year".
                // Make sure no results shown.
                return $queryBuilder->andWhere('1 = 0');
            }

            $end->modify('+7 days');
        } else {
            $start = WeekNumberHelper::getFirstDayOfYear($formData['year']);
            $end = WeekNumberHelper::getFirstDayOfYear($formData['year'] + 1);
        }

        return $queryBuilder
            ->andWhere($queryBuilder->expr()->andX(
                "{$this->getPropertyPath()} >= :{$this->getId()}_start",
                "{$this->getPropertyPath()} < :{$this->getId()}_end"
            ))
            ->setParameter("{$this->getId()}_start", $start)
            ->setParameter("{$this->getId()}_end", $end);
    }
}