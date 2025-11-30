<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * CallType models the call types option in the call information section of logged calls.
 */
enum CallType: string
{
    case INBOUND  = 'Inbound';
    case MISSED   = 'Missed';
    case OUTBOUND = 'Outbound';
}
