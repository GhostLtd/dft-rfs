/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
'use strict'

import * as gds from 'govuk-frontend';
import accessibleAutocomplete from 'accessible-autocomplete';
import 'accessible-autocomplete/dist/accessible-autocomplete.min.css';

gds.initAll();

const autocompleteElements = document.getElementsByClassName('accessible-autocomplete')
for(var i=0; i<autocompleteElements.length; i++) {
    accessibleAutocomplete.enhanceSelectElement({
        selectElement:autocompleteElements[i],
    })
}

// any SCSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';


/*
document.addEventListener('click', function (event) {
    // If the clicked element doesn't have the right selector, bail
    if (!event.target.matches('.govuk-back-link')) return;

    // Don't follow the link
    event.preventDefault();

    // Log the clicked element in the console
    window.history.back();
}, false);
*/

console.log('js');
