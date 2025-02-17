<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Form\Admin\AbstractImportReviewDataType;
use App\Utility\RegistrationMarkHelper;

class ImportDvlaReviewDataType extends AbstractImportReviewDataType
{
    #[\Override]
    protected function choiceLabel($data): string
    {
        /** @var Survey $data */
        $regMark = new RegistrationMarkHelper($data->getRegistrationMark());
        $address1 = ucwords(strtolower($data->getInvitationAddress()->getLine1()));
//        $email = strtolower($data->getTransientInvitationEmails());

        $safeLabel = htmlspecialchars(
            "{$address1}, {$data->getInvitationAddress()->getPostcode()}",
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeRegMark = htmlspecialchars(
            $regMark->getFormattedRegistrationMark(),
            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
//        $safeEmail = htmlspecialchars(
//            $email,
//            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
//        $emailLabel = $email ? " <i>($safeEmail)</i>" : "";
        $emailLabel = ($data->getTransientNotificationInterception() ? '<strong class="govuk-tag">LCNI</strong>' : '');
        return "<b>{$safeRegMark}</b> {$safeLabel}{$emailLabel}";
    }
}
