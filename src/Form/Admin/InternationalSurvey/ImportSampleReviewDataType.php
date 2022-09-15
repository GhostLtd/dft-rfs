<?php


namespace App\Form\Admin\InternationalSurvey;


use App\Entity\International\Survey;
use App\Entity\PreEnquiry\PreEnquiry;
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

    protected function choiceLabel($data): string
    {
        switch (get_class($data)) {
            case Survey::class:
                return $this->internationalChoiceLabel($data);

            case PreEnquiry::class:
                return $this->preEnquiryChoiceLabel($data);
        }
        return '';
    }

    protected function internationalChoiceLabel($data): string
    {
        /** @var Survey $data */
        $startDate = $data->getSurveyPeriodStart()->format($this->translator->trans('format.date.default'));
//        $endDate = $data->getSurveyPeriodEnd()->format($this->translator->trans('format.date.default'));
        $diff = $data->getSurveyPeriodEnd()->diff($data->getSurveyPeriodStart());
        $diff = $diff->days + 1;
        $address1 = ucwords(strtolower($data->getCompany()->getBusinessName()));

        $safeLabel = htmlspecialchars(
            "{$address1}, {$data->getInvitationAddress()->getPostcode()}",
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<b>{$startDate} ({$diff}d)</b> {$safeLabel}" . ($data->getInvitationEmails() ? '<strong class="govuk-tag">LCNI</strong>' : '');
    }

    protected function preEnquiryChoiceLabel($data): string
    {
        /** @var PreEnquiry $data */
        $address1 = ucwords(strtolower($data->getCompanyName()));

        $safeLabel = htmlspecialchars(
            "{$address1}, {$data->getInvitationAddress()->getPostcode()}",
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeRef = htmlspecialchars($data->getReferenceNumber());
        return "<b>{$safeRef}</b> {$safeLabel}";
    }
}