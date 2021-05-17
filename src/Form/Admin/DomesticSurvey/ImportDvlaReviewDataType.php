<?php


namespace App\Form\Admin\DomesticSurvey;


use App\Entity\Domestic\Survey;
use App\Form\Admin\AbstractImportReviewDataType;
use App\Utility\Domestic\DvlaImporter;
use App\Utility\RegistrationMarkHelper;

class ImportDvlaReviewDataType extends AbstractImportReviewDataType
{
    protected function choiceLabel($data) {
        /** @var Survey $data */
        $regMark = new RegistrationMarkHelper($data->getRegistrationMark());
        $address1 = ucwords(strtolower($data->getInvitationAddress()->getLine1()));

        $safeLabel = htmlspecialchars(
            "{$address1}, {$data->getInvitationAddress()->getPostcode()}",
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeRegMark = htmlspecialchars(
            $regMark->getFormattedRegistrationMark(),
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return "<b>{$safeRegMark}</b> {$safeLabel}";
    }
}