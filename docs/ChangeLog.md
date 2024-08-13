[Home](../README.md) > Change log

# Changelog

## Upcoming

* **Fix:** IRHS "send reminder unavailable" page was broken

## 22nd July 2024

* **Feature:** Further increase measure to further prevent two different wizards from running at once

## 2nd July 2024

* **Feature:** Deletion of old data (2 years), and personal data (1 year)
* **Fix:** Admin - pre-enquiry list page has no default sort order
* **Fix:** Admin - fix error on Operator Groups list page
* **Fix:** Admin - show warning notification upon failed transition event (e.g. trying to close an already-closed survey)
* **Fix:** Admin - CSRGT summary days are showing distance where they should be showing weight
* **Fix:** Admin - CSRGT missing "capacity" data when truck is empty

## 21st May 2024

* **Feature:** Use extracted govuk-frontend-bundle and in the process upgrade to govuk-frontend 5.3.1
* **Feature:** Admin - IRHS/Pre/RoRo - add links on survey view screens to list page (searching by company) and other related pages
* **Fix:** Admin - CSRGT - enter-initial-details screen could be visited even after initial details added
* **Fix:** Cancel button on survey feedback screen triggers validation
* **Change:** Admin - Driver-availability export - change so that export is now by year/month
* **Change:** Update various copy to change "> 3.5t" to "HGV"  

## 17th April 2024

* **Feature:** IRHS - Loading without unloading checks
* **Feature:** CSRGT - Add GrossWeight to the export
* **Feature:** CSRGT Admin - add a flag for likely hire companies on the list screen and survey view screen  
* **Fix:** Fix bug on RoRo admin unassign route screen
* **Fix:** Fix "Session was used while the request was declared stateless" error in dev environment
* **Fix:** Fix bug on Domestic where crash could be caused by visiting summary wizard on a stop day or vice-versa

## 2nd April 2024

* **Change:** Upgrade to Symfony 6.4 and PHP 8.3
* **Change:** Utilise Rector and Phpstan to improve code quality and add further tests
* **Fix:** Fix problem with DecimalToStringTransformer that caused an error  

## 26th Mar 2024

* **Feature:** Add functional tests for IRHS wizards: initial details, add vehicle, add trip, add action
* **Feature:** When deleting/updating/adding LCNI, update emails on relevant surveys
* **Change:** Upgrade govuk-frontend to 3.15 and start using the Tudor Crown branding
* **Change:** Various minor changes to combat occasional user login-related confusions caused by copy/paste and letter/form differences

## 18th Jan 2024

* **Fix:** CSRGT Admin - List page filters: Allow next year to be chosen, and don't break if week 53 selected on a year that doesn't have that week. 
* **Change:** CSRGT - update "The vehicle was not taxed" answer to mention "SORN".
* **Change:** Roro Admin - Dev-mode auto-login is now a feature rather than having its own env flag.
* **Feature:** Admin - add a simple utility to lookup user identifiers and find their corresponding CSRGT/IRHS/PreEnquiry survey.
* **Change:** Pre-enquiry - remove approved state
* **Change:** RoRo - add SurveyState to export data
* **Feature:** CSRGT - when reissuing a survey, autofill recipient email from hiree/new owner
* **Feature:** IRHS - validate reference number when adding a survey manually, to make sure that it includes a week number
* **Feature:** CSRGT / IRHS - Early response question in closing details
* **Change:** CSRGT - minor change to help text of initial details > contact details > business name
* **Change:** CSRGT / IRHS / Pre-enquiry - make user/pass input fields tolerant of spaces and/or a prefixed # symbol

## 4th January 2024

* **Feature:** Add a checkbox to the CSRGT DVLA import screen allowing surveys to be created in the past 

## 19th December 2023

* **Feature:** RoRo - Operator Groups  
  - Operators groups can be created by the admin
  - Operators can then switch between Operators whose name starts with the Operator Group's name.
    - e.g. Given Operator group "Toast Ferries" and operators "Toast Ferries - Plymouth" and "Toast Ferries - Portsmouth",
           users of these operators would be able to switch between them (in order to administrate each other's surveys).
  - As a part of this update, the "route bookmarking" feature has been removed, as it was intended to perform a similar role.
* **Change:** RoRo - bulk data entry now accepts "unacc" as a key for unaccompanied trailer data
* **Change:** RoRo - show data entry method used in admin when viewing survey, and also in exports
* **Change:** Add admin dashboard entries for Pre-Enquiry / RoRo
* **Change:** RoRo - show operator/port numbers on RoRo admin list page, and allow searching by them
* **Change:** RoRo - Add OperatorGroup-related guidance text to the Operator view page
* **Fix:** RoRo - APP_DISABLE_REMINDERS environment var now correctly passed through to app
* **Fix:** RoRo - reminder should be sent on day 8 rather than day 9

## 1st November 2023

* **Fix:** Pre-enquiry - Fix export crash when unfilled survey closed by admin
* **Change:** Pre-enquiry - Disallow state change to CLOSED when survey not filled
* **Change:** RoRo + Pre-enquiry - export surveys in both CLOSED and APPROVED states
* **Change:** RoRo - remove the QA mechanism
* **Change:** RoRo - export now includes isApproved flag
* **Fix:** RoRo - route + operator codes now 2-digit zero-padded in exports
* **Fix:** RoRo - when survey filled, empty counts should be exported as zero rather than NULL

## 30th October 2023

* **Update:** Maintenance banners now additionally shown on each survey's dashboard.
* **Change:** RoRo - Export isActive and comments fields, making sure all count fields are NULL when isActive is false.
* **Fix:** RoRo - Turn autocomplete off for country vehicle count form as Safari wants to autocomplete these fields.
* **Fix:** RoRo - Fix duplicate email address error when adding a new operator user.
* **Fix:** RoRo - Fix missing operator code and port code validation.

## 24th October 2023 

* **Feature:** [RoRo survey](./RoRo.md)
* **Change:** Smartlook integration and removal of Wisdom.
* **Fix:** A bug was causing empty email addresses to be passed to Notify.
* **Fix:** NotifyApiResponse should have a nullable recipientHash.
* **Fix:** Notification sent date is now recorded when processing messages (rather than on successful completion) to 
avoid perpetual requeue of failed messages.
* **Fix**: Update dbrekelmans/bdi to fix broken Selenium driver updates for Chrome.
* **Fix**: Fix LCNI validation
* **Fix**: Fix broken date text searches in admin, and allow date searching on IRHS admin list page
* **Feature**: Roro screenshots
* **Change:** Change port edit voter (can only edit ports that aren't a part of a route)
* **Feature**: Implement log processors to add session_id and user_id to log entries
* **Feature**: Add ability to be able to selectively disable reminders for each survey type via the APP_DISABLE_REMINDERS environment variable

## 7th June 2023

* **Feature:** Feedback form
* **Fix:** A bug was causing a crash in pre-enquiry when the invitation address was not set.
* **Fix:** Auto generate any missing proxy files in production.

## 16th February 2023

* **Change:** Update IRHS Trip Start/End form copy to highlight that these should be UK locations.
* **Change:** CSRGT: Validation change - Day summary's number of stops must total at least five. 
* **Feature:** Add routines to purge inconsistent survey data (e.g. has journeys, but reason-for-empty-survey filled).
* **Feature:** Can now use markdown in labels + help (merged from NTS).
* **Fix:** A bug was causing empty email addresses to be passed to Notify.
* **Fix:** NotifyApiResponse should have a nullable recipientHash.
* **Fix:** A bug was a crash in ManualReminderHelper when approving and then re-opening an empty survey.
* **Fix:** Small bug in display of approvals report.
* **Fix:** Don't show .env.local during cloud build.
* **Fix:** "Enter business and vehicle details" button not showing in admin.

## 2nd February 2023

* **Feature:** Manual reminders
  * Manual email reminders can now be triggered via the admin for in-progress surveys.  
    See [Reminders.md](./Reminders.md) for full details.
* **Feature:** CSRGT - Exempt vehicles
  * The "in-possesion of vehicle?" question now has a sub-question if answered with "yes"
  * The sub-question asks whether the user's vehicle is a horsebox/jetter etc
  * If it is, then the survey can then be directly submitted from the following page (to REJECTED state)
* **Feature:** CSRGT - Not-in-possession surveys can now be directly closed by the user.
  * This allows the user to skip the Driver Availability questions, which are not relevant to them.
  * The survey ends up in the CLOSED state.
* **Fix:** CSRGT Admin - Export - MOA was being exported as NULL rather than "NS".  
* **Fix:** IRHS Admin - "Final details" tab disappears when survey rejected
* **Fix:** CSRGT Admin - Reports - Remove errant "pre-enquiry" option in survey type dropdowns
* **Fix:** CSRGT Admin - Remove errant title on day stop stage delete page.
* **Change:** Change to lorry size description so that it's more obvious survey is only for vehicles of 3.5 tonnes and over.
* **Refactor:** [SurveyTrait](../src/Entity/SurveyTrait.php) / [SurveyInterface](../src/Entity/SurveyInterface.php): Rename notifiedDate -> invitationSentDate (to clarify field purpose)
* **Feature:** New improved [project documentation](../README.md)