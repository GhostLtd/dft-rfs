[Home](../README.md) > Change log

# Changelog

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