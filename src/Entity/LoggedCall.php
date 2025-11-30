<?php

declare(strict_types=1);

namespace App\Entity;

use DateInterval;
use DateTime;

/**
 * This class stores a small number of details of a contact retrieved from Zoho CRM.
 *
 * @see https://www.zoho.com/developer/help/api/modules-fields.html#Contacts
 */
class LoggedCall
{
    public CallPurpose|null $callPurpose = null;
    public CallResult|null $callResult   = null;
    public CallType $callType            = CallType::INBOUND;
    public DateInterval $callDuration;
    public DateTime $callStarted;
    public OutgoingCallStatus $outgoingCallStatus = OutgoingCallStatus::NONE;

    public string $callAgenda     = "";
    public string $description    = "";
    public string $subject        = "";
    public string $voiceRecording = "";

    // Need to figure out how to set these
    public string $callFor;
    public string $relatedTo;
}
