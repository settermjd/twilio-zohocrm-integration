<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * OutgoingCallStatus models the outgoing call status options in the "Purpose of Outgoing Call" section of logged calls.
 */
enum OutgoingCallStatus: string
{
    case CANCELLED = 'Cancelled';
    case COMPLETED = 'Completed';
    case NONE      = '-None-';
    case OVERDUE   = 'Overdue';
    case SCHEDULED = 'Scheduled';
}
