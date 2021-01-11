/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 *
 * Coding standards...
 * https://gds-way.cloudapps.digital/manuals/programming-languages/js.html
 */
'use strict'

import './app';

// import '../bundles/Ghost/GovUkFrontendBundle/Resources/assets/css/bundle.scss'
import './styles/admin/admin.scss';
require('./js/admin/blame-log');

GOVUK.rfsBlameLog.initAll();
