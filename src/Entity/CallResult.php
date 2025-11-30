<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * CallType models the call types option in the call information section of logged calls.
 */
enum CallResult: string
{
    case INTERESTED          = 'Interested';
    case INVALID_NUMBER      = 'Invalid number';
    case NONE                = '-None-';
    case NOT_INTERESTED      = 'Not interested';
    case NO_RESPONSE_BUSY    = 'No response/Busy';
    case REQUESTED_CALL_BACK = 'Requested call back';
    case REQUESTED_MORE_INFO = 'Requested more info';
}
