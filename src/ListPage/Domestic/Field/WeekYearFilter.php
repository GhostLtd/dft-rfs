<?php


namespace App\ListPage\Domestic\Field;


use App\Form\Admin\DomesticSurvey\SurveyListField\WeekYearFilterType;
use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use App\Utility\Domestic\WeekNumberHelper;
use Doctrine\ORM\QueryBuilder;

class WeekYearFilter extends Simple implements FilterableInterface
{
    private array $formOptions;

    public function __construct(string $label, string $propertyPath, $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
        $this->formOptions = $formOptions;
    }

    public function getFormOptions(): array
    {
        return array_merge([
            // no default options
        ], $this->formOptions);
    }

    public function getFormClass(): string
    {
        return WeekYearFilterType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if (!isset($formData['year']) || !is_numeric($formData['year'])) {
            return $queryBuilder;
        }

        if (isset($formData['week']) && is_numeric($formData['year'])) {
            $start = WeekNumberHelper::getDate($formData['year'], $formData['week']);
            $end = WeekNumberHelper::getDate($formData['year'], $formData['week']);
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