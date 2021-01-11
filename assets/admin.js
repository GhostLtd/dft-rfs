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

// any SCSS you import will output into a single css file (app.css in this case)
import './styles/admin/admin.scss';
require('./js/admin/blame-log');

GOVUK.rfsBlameLog.initAll();
