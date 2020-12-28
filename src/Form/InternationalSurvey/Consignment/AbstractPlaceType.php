<?php

namespace App\Form\InternationalSurvey\Consignment;

use App\Entity\International\Consignment;
use App\Entity\International\Stop;
use App\Repository\International\StopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractPlaceType extends AbstractType
{
    private $stopRepository;

    private $requestStack;

    public function __construct(StopRepository $stopRepository, RequestStack $requestStack)
    {
        $this->stopRepository = $stopRepository;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $minStop = is_callable($options['minimum_stop'])
                ? $options['minimum_stop']($event)
                : $options['minimum_stop'];
            $choices = $this->getChoicesForStops($this->stopRepository->getStopsForConsignment($event->getData(), $minStop));
            $event->getForm()
                ->add('place', Gds\ChoiceType::class, [
                    'label_is_page_heading' => true,
                    'label' => "{$options['translation_key_prefix']}.label",
                    'label_attr' => ['class' => 'govuk-label--xl'],
                    'help' => "{$options['translation_key_prefix']}.help",
                    'choices' => $choices,
                    'property_path' => $options['child_property_path'],
                ]);
        });
    }

    /**
     * @param ArrayCollection | Stop[] $stops
     * @return Stop[]
     */
    protected function getChoicesForStops($stops): array
    {
        $choices = [];
        foreach ($stops as $stop) {
            $choices[$this->getLabelForStop($stop)] = $stop;
        }
        return $choices;
    }

    protected function getLabelForStop(Stop $stop): string
    {
        $country = Countries::getName(strtoupper($stop->getCountry()), $this->requestStack->getCurrentRequest()->getLocale());
        return "{$stop->getName()}, {$country} (stop {$stop->getNumber()})";
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Consignment::class,
            'minimum_stop' => 0,
        ]);
        $resolver->setRequired([
            'translation_key_prefix',
            'child_property_path',
        ]);
        $resolver->setAllowedValues('translation_key_prefix', [
            "international.consignment.place.place-of-loading",
            "international.consignment.place.place-of-unloading",
        ]);
    }
}
