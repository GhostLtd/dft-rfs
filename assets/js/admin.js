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

import './common';

import '../styles/admin.scss';
import './admin/blame-log';
import './admin/dashboard';
import './admin/irhs-trip';

GOVUK.rfsBlameLog.initAll();
GOVUK.rfsDashboard.init();
GOVUK.rfsIrhsTrip.init();
