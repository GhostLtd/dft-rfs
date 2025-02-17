<?php

namespace App\Form\Admin\LCNI;

use App\Entity\NotificationInterception;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

class NotificationInterceptionDataMapper extends DataMapper
{
    /**
     * @param NotificationInterception $data
     */
    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $forms = iterator_to_array($forms);
        if ($forms['isUsingSameEmails'] ?? false) {
            if ($forms['isUsingSameEmails']->getData()) {
                $data->setCommonEmails($forms['commonEmails']->getData());
            } else {
                $data->setCsrgtEmails($forms['csrgtEmails']->getData());
                $data->setIrhsEmails($forms['irhsEmails']->getData());
            }
        }
    }
}