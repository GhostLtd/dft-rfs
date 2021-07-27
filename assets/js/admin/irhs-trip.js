;(function (global) {
    'use strict'
    let GOVUK = global.GOVUK || {};

    let axleConfigInput, bodyTypeInput, weightInputGroups;

    GOVUK.rfsIrhsTrip = {
        _init: function() {
            let tripForm = document.querySelector("form[name=trip]");
            if (tripForm === null) {
                return;
            }

            axleConfigInput = document.getElementById("trip_otherDetails_axle_config");
            bodyTypeInput = document.getElementById("trip_otherDetails_body_type");
            let weightInputs = document.querySelectorAll("#trip_otherDetails_gross_weight, #trip_otherDetails_carrying_capacity");
            weightInputGroups = Array.from(weightInputs).map(item => item.closest('.govuk-form-group'));

            // Copied from gds.Radio.init -->
            if ('onpageshow' in window) {
                window.addEventListener('pageshow', this.syncConditionalVehicleWeightsReveal.bind(this));
            } else {
                window.addEventListener('DOMContentLoaded', this.syncConditionalVehicleWeightsReveal.bind(this));
            }

            this.syncConditionalVehicleWeightsReveal();
            // <-- Copied from gds.Radio.init

            axleConfigInput.addEventListener('change', this.syncConditionalVehicleWeightsReveal.bind(this));
            bodyTypeInput ? bodyTypeInput.addEventListener('change', this.syncConditionalVehicleWeightsReveal.bind(this)) : null;
        },

        syncConditionalVehicleWeightsReveal: function() {
            if (this.canChangeWeights()) {
                weightInputGroups.map(item => item.classList.remove('govuk-!-display-none'));
                weightInputGroups.map(item => item.querySelector('input').disabled = false);
            } else {
                weightInputGroups.map(item => item.classList.add('govuk-!-display-none'));
                weightInputGroups.map(item => item.querySelector('input').disabled = true);
            }
        },

        /**
         * This logic should exactly mirror that in
         * php Entity\International\Trip::canChangeWeights()
         */
        canChangeWeights: function() {
            let swappedTrailer = (axleConfigInput.value !== '0');
            let bodyTypeChanged = bodyTypeInput ? (bodyTypeInput.value !== '0') : false;

            return swappedTrailer || bodyTypeChanged;
        }
    }

    GOVUK.rfsIrhsTrip.init = GOVUK.rfsIrhsTrip._init.bind(GOVUK.rfsIrhsTrip);

    global.GOVUK = GOVUK;
})(window); // eslint-disable-line semi
