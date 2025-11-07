<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

class EventOrganiser
{
    #[JSON(field: 'id')]
    public string $id;

    #[JSON(field: 'email')]
    public string $email;

    #[JSON(field: 'name')]
    public string $name;
}
