<?php


namespace App\Form\InternationalSurvey\Consignment;


use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceOfLoadingType extends AbstractPlaceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => 'loading-stop',
            'translation_key_prefix' => 'international.consignment.place.place-of-loading',
            'child_property_path' => 'loadingStop',
        ]);
    }
}
