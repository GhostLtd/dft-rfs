<?php


namespace App\Form\InternationalSurvey\Consignment;


use App\Entity\International\Consignment;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlaceOfUnloadingType extends AbstractPlaceType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'validation_groups' => 'unloading-stop',
            'translation_key_prefix' => 'international.consignment.place.place-of-unloading',
            'child_property_path' => 'unloadingStop',
            'minimum_stop' => function(FormEvent $event){
                /** @var Consignment $consignment */
                $consignment = $event->getData();
                return ($consignment->getLoadingStop()->getNumber() ?? 0);
            },
        ]);
    }
}
