'use strict'

// import accessibleAutocomplete from 'accessible-autocomplete';

import '../../vendor/ghost/govuk-frontend-bundle/assets/scss/ghost-frontend.scss';
import '../styles/common.scss';
// import 'accessible-autocomplete/dist/accessible-autocomplete.min.css';
import {ghostFrontendInitAll} from '@ghost/govuk-frontend-bundle/assets/js/ghost-frontend';

// GOV.UK Frontend components (with the bundle's GhostRadios, which supports data-aria-hide-controls),
// plus show-password and double-submit prevention
ghostFrontendInitAll();

// const autocompleteElements = document.getElementsByClassName('accessible-autocomplete')
// for(var i=0; i<autocompleteElements.length; i++) {
//     accessibleAutocomplete.enhanceSelectElement({
//         selectElement:autocompleteElements[i],
//     })
// }
