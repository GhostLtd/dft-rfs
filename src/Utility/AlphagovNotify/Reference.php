<?php


namespace App\Utility\AlphagovNotify;

class Reference
{
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_FAILED = 'failed';
    const STATUS_WAITING_FOR_RETRY = 'waiting-for-retry';

    const EVENT_INVITE = 'invite';
    const EVENT_REMINDER_1 = 'reminder-1';
    const EVENT_REMINDER_2 = 'reminder-2';

    // TODO: change development email template
    const EMAIL_DOMESTIC_SURVEY_INVITE = '';

    // TODO: change private beta letter template
    const LETTER_DOMESTIC_SURVEY_INVITE = 'd271ef54-57eb-43a3-a0fb-53632ee29caa';

    // Todo: Change development email template
    const EMAIL_INTERNATIONAL_SURVEY_INVITE = '';

    // TODO: change private beta letter template
    const LETTER_INTERNATIONAL_SURVEY_INVITE = '8353aa84-0922-4eab-a1fd-1240a6cabbcc';

    const LETTER_DOMESTIC_SURVEY_REMINDER_1 = '0f35d245-caf8-4404-940a-20c5fdc62456';
    const LETTER_DOMESTIC_SURVEY_REMINDER_2 = '09854464-51fd-4aef-9566-8dab5421a493';

    const LETTER_INTERNATIONAL_SURVEY_REMINDER_1 = '81ac1f50-f9f6-4e6b-9dd4-c268c3af3106';
    const LETTER_INTERNATIONAL_SURVEY_REMINDER_2 = '89723e0f-12f9-43c2-b074-3ec548377526';
}
