<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\AbstractGoodsDescriptionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GoodsDescriptionType extends AbstractGoodsDescriptionType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_entity_key' => 'international.action',
            'is_summary_day' => true,
        ]);
    }
}
