<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * CallType models the call purpsose option in the "Purpose of Outgoing Call" section of logged calls.
 */
enum CallPurpose: string
{
    case ADMINISTRATIVE = 'Administrative';
    case DEMO           = 'Demo';
    case DESK           = 'Desk';
    case NEGOTIATION    = 'Negotation';
    case PROJECT        = 'Project';
    case PROSPECTING    = 'Prospecting';
    case NONE           = '-None-';
}
