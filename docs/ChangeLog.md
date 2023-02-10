[Home](../README.md) > Change log

# Changelog

## Upcoming

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