<?php

declare(strict_types=1);

namespace App\Entity\SearchResponse;

use JSON\Attributes\JSON;

/**
 * This class stores a small number of details of an event.
 * The information is retrieved from Zoho CRM.
 *
 * @see https://www.zoho.com/developer/help/api/modules-fields.html#Events
 */
class EventParticipant
{
    #[JSON(field: 'id')]
    public string $id;

    // This is the contact's internal id
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
