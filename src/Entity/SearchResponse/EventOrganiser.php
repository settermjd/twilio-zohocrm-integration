<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

/**
 * This class stores a small number of details of an event's organiser.
 * The information is retrieved from Zoho CRM.
 */
class EventOrganiser
{
    #[JSON(field: 'id')]
    public string $id;

    #[JSON(field: 'email')]
    public string $email;

    #[JSON(field: 'name')]
    public string $name;
}
