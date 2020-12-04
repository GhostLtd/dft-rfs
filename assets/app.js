/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
'use strict'

import '../bundles/Ghost/GovUkFrontendBundle/Resources/assets/js/bundle';
import * as gds from 'govuk-frontend';

gds.initAll();

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
