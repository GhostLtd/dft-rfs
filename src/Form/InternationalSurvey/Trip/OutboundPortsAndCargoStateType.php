<?php

namespace App\Form\InternationalSurvey\Trip;

use Symfony\Component\OptionsResolver\OptionsResolver;

class OutboundPortsAndCargoStateType extends AbstractPortsAndCargoStateType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'direction' => 'outbound',
            'validation_groups' => ['trip_outbound_ports', 'trip_outbound_cargo_state'],
            'at_capacity_null_message' => 'international.trip.outbound.at-capacity-not-null',

            'cargo_at_capacity_label' => 'international.trip.outbound-cargo-state.was-at-capacity.label',
            'cargo_at_capacity_help' => 'international.trip.outbound-cargo-state.was-at-capacity.help',
            'cargo_empty_label' => 'international.trip.outbound-cargo-state.was-empty.label',
            'cargo_empty_help' => 'international.trip.outbound-cargo-state.was-empty.help',
            'cargo_limited_label' => 'international.trip.outbound-cargo-state.was-limited.label',
            'cargo_limited_help' => 'international.trip.outbound-cargo-state.was-limited.help',

            'ports_label' => 'international.trip.outbound-ports.ports.label',
            'ports_help' => 'international.trip.outbound-ports.ports.help',
        ]);
    }
}
