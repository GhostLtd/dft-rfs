'use strict'

import * as gds from 'govuk-frontend';
import accessibleAutocomplete from 'accessible-autocomplete';
import radiosOverride from "./radios";

import '../../bundles/Ghost/GovUkFrontendBundle/Resources/assets/css/bundle.scss';
import '../styles/common.scss';
import 'accessible-autocomplete/dist/accessible-autocomplete.min.css';
import doubleSubmitPrevention from "./double-submit-prevention";

radiosOverride();
gds.initAll();
doubleSubmitPrevention();

const autocompleteElements = document.getElementsByClassName('accessible-autocomplete')
for(var i=0; i<autocompleteElements.length; i++) {
    accessibleAutocomplete.enhanceSelectElement({
        selectElement:autocompleteElements[i],
    })
}
