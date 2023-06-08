[Home](../README.md) > Reminders

# Reminders (CSRGT, IRHS, Pre-enquiry)

There are two types of reminders:

* [Automated reminders for non-started surveys](#automated-reminders-for-non-started-surveys) which are triggered via cron
* [Manual reminders](#manual-reminders) which are triggered via the admin panel

Sending dates for these reminders are kept in various Survey fields (e.g. firstReminderSentDate), and API responses 
detailing success or failure are kept in Surveys' apiResponses field.

@see [SurveyTrait.php](../src/Entity/SurveyTrait.php)

## Automated reminders for non-started surveys

These reminders are sent both by __letter__ and __email__ (if available), and are used to reminder DiaryKeepers that 
they need to start their diary.

A DiaryKeeper is deemed to have started their diary once a survey response has been committed to the database, which
generally equates to the first survey wizard having been completed.

@see [AutomatedRemindersHelper.php](../src/Utility/Reminder/AutomatedRemindersHelper.php)

### First reminder (CSRGT, IRHS):

The first reminder is sent if the following conditions are met:

* The survey is in either the NEW or INVITATION_SENT state.
* Neither a first nor second reminder has previously been sent.
* The survey ended no less than __seven__ days ago.
* The survey invitation was sent no less than __seven__ days ago.

### First reminder (PRE):

The first reminder is sent if the following conditions are met:

* The survey is in either the NEW or INVITATION_SENT state.
* Neither a first nor second reminder has previously been sent.
* The survey invitation was sent no less than __fourteen__ days ago.

### Second reminder (CSRGT, IRHS, PRE):

The second reminder is sent if the following conditions are met:

* The survey is in either the NEW or INVITATION_SENT state.
* A second reminder has not previously been sent.
* The first reminder was sent no less than __fourteen__ days ago.

## Manual reminders

These reminders are sent via __email__ only, and can only be sent for surveys that are __in progress__. Reminders are sent to either the survey's contact email, if provided, or to the 
survey invitation emails. If neither of these are available, then a manual reminder cannot be sent. 

A manual reminder can only be sent if at least __seven__ days have passed since the most recent of any of these events:

* Initial survey invitation
* Survey state change to in-progress
* Sending of first/second/manual reminder

@see [ManualReminderHelper.php](../src/Utility/Reminder/ManualReminderHelper.php)