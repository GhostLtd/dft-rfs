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
import './session-reminder';

import '../styles/app.scss';

GOVUK.sessionReminder.init();