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
        return "<b>{$regMark->getFormattedRegistrationMark()}</b> {$address1}, {$data->getInvitationAddress()->getPostcode()}";
    }
}