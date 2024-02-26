<?php


namespace App\Utility\AlphagovNotify;

class Reference
{
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_WAITING_FOR_RETRY = 'waiting-for-retry';

    public const EVENT_INVITE = 'invite';
    public const EVENT_REMINDER_1 = 'reminder-1';
    public const EVENT_REMINDER_2 = 'reminder-2';
    public const EVENT_MANUAL_REMINDER = 'reminder-manual';

    public const LETTER_DOMESTIC_SURVEY_INVITE = 'd271ef54-57eb-43a3-a0fb-53632ee29caa';
    public const LETTER_DOMESTIC_SURVEY_REMINDER_1 = '2aa33b37-40f9-4dca-8701-bf080d02563c';
    public const LETTER_DOMESTIC_SURVEY_REMINDER_2 = 'c98fce59-92f1-4b4e-abcc-f0f4ff8da729';
    public const EMAIL_DOMESTIC_SURVEY_INVITE = '5542fa59-3a31-4e01-b1b0-9c0038413807';
    public const EMAIL_DOMESTIC_SURVEY_REMINDER_1 = 'd0773bd6-1773-418d-b5f5-aa7ce5ee8891';
    public const EMAIL_DOMESTIC_SURVEY_REMINDER_2 = '30771400-28c2-455f-b797-4bbbd5fd39da';
    public const EMAIL_DOMESTIC_SURVEY_MANUAL_REMINDER = '483246e7-5b94-4eeb-b314-64ee8188a69d';

    public const LETTER_INTERNATIONAL_SURVEY_INVITE = '8353aa84-0922-4eab-a1fd-1240a6cabbcc';
    public const LETTER_INTERNATIONAL_SURVEY_REMINDER_1 = '557d45de-3b80-4cda-a82a-f14ea263463b';
    public const LETTER_INTERNATIONAL_SURVEY_REMINDER_2 = 'ab17c4ae-c60d-4ecc-8f41-58fbcec31636';
    public const EMAIL_INTERNATIONAL_SURVEY_INVITE = 'd761a146-93d3-4b53-9aa2-cea080eea228';
    public const EMAIL_INTERNATIONAL_SURVEY_REMINDER_1 = '235c38b6-fb6c-4c52-a858-973cb195d276';
    public const EMAIL_INTERNATIONAL_SURVEY_REMINDER_2 = 'b3ee2631-bc3c-4c3e-92e7-e53282334467';

    public const EMAIL_INTERNATIONAL_SURVEY_MANUAL_REMINDER = '12fa9aab-0561-45f4-b4b5-a437646fdf10';

    public const LETTER_PRE_ENQUIRY_INVITE = '8ed795ea-e56c-42af-8684-c3469a5336f6';
    public const LETTER_PRE_ENQUIRY_REMINDER_1 = '81d556d2-ea6e-4f73-a43a-8b0a59425463';
    public const LETTER_PRE_ENQUIRY_REMINDER_2 = '09c7ccdb-fb4c-4788-ad44-5650af076ed9';
    public const EMAIL_PRE_ENQUIRY_INVITE = '';
    public const EMAIL_PRE_ENQUIRY_REMINDER_1 = '';
    public const EMAIL_PRE_ENQUIRY_REMINDER_2 = '';

    public const RORO_LOGIN_LINK = 'a934719d-d978-4add-84d2-d0e7229679c7';

    public const EMAIL_RORO_REMINDER_1 = '7c9c1c43-e446-47f0-8a7c-96784d8966ae';
    public const EMAIL_RORO_REMINDER_2 = 'bd930a59-3783-4ae9-aa4d-e0d86a8412d9';
}
