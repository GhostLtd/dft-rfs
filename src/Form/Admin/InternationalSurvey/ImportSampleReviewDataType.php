<?php


namespace App\Form\Admin\InternationalSurvey;


use App\Entity\International\Survey;
use App\Form\Admin\AbstractImportReviewDataType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImportSampleReviewDataType extends AbstractImportReviewDataType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    protected function choiceLabel($data) {
        /** @var Survey $data */
        $startDate = $data->getSurveyPeriodStart()->format($this->translator->trans('format.date.short'));
        $endDate = $data->getSurveyPeriodEnd()->format($this->translator->trans('format.date.short'));
        $diff = $data->getSurveyPeriodEnd()->diff($data->getSurveyPeriodStart());
        $diff = $diff->days + 1;
        $address1 = ucwords(strtolower($data->getCompany()->getBusinessName()));

        $safeLabel = htmlspecialchars(
            "{$address1}, {$data->getInvitationAddress()->getPostcode()}",
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<b>{$startDate} ({$diff}d)</b> {$safeLabel}";
    }
}