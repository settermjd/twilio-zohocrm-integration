<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

/**
 * This class stores a small number of details of an event retrieved from Zoho CRM.
 *
 * @see https://www.zoho.com/developer/help/api/modules-fields.html#Events
 */
class Event
{
    #[JSON(field: 'Venue')]
    public string $venue;

    #[JSON(field: 'Event_Title')]
    public string $title;

    #[JSON(field: 'Start_DateTime')]
    public string $startsAt;

    #[JSON(field: 'Owner', type: EventOrganiser::class)]
    public EventOrganiser $organiser;

    // @var list<EventParticipant>
    #[JSON(field: 'Participants', type: EventParticipant::class)]
    public array $participants;
}
