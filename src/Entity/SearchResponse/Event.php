<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

class Event
{
    #[JSON(field: 'Venue')]
    public string $venue;

    #[JSON(field: 'Event_Title')]
    public string $title;

    #[JSON(field: 'Start_DateTime')]
    public string $startsAt;

    /**
     * @var list<EventParticipant>
     */
    #[JSON(field: 'Participants', type: EventParticipant::class)]
    public array $participants;
}
