[Home](../README.md) > Change log

# Changelog

## Upcoming

* **Change:** RoRo - bulk data entry now accepts "unacc" as a key for unaccompanied trailer data
* **Change:** RoRo - show data entry method used in admin when viewing survey, and also in exports

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