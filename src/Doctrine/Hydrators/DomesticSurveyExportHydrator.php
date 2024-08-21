<?php


namespace App\Doctrine\Hydrators;


use Doctrine\ORM\Internal\Hydration\ObjectHydrator;

class DomesticSurveyExportHydrator extends ObjectHydrator
{
    #[\Override]
    protected function hydrateAllData(): array
    {
        $result = parent::hydrateAllData();
        foreach ($result as $item=>$value) {
            if ($value[0]->getResponse()) {
                $value[0]->getResponse()->_summaryCountForExport = $value['summaryCount'];
                $value[0]->getResponse()->_stopCountForExport = $value['stopCount'];
            }
            $result[$item] = $value[0];
        }
        return $result;
    }
}