<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

class EventParticipant
{
    #[JSON(field: 'id')]
    public string $id;

    // This is the contact's internal id, not the one labelled id.
    // Go figure!!
    #[JSON(field: 'participant')]
    public string $participant;

    #[JSON(field: 'name')]
    public string $name;

    #[JSON(field: 'Email')]
    public string $email;

    /**
     * This stores the participant's contact details, as they're only available
     * via a separate API request
     */
    public Contact $contactDetails;
}
