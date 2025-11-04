<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

class Contact
{
    #[JSON(field: 'id')]
    public string $id;

    #[JSON(field: 'Phone')]
    public string $phone;

    #[JSON(field: 'Mobile')]
    public string $mobile;
}
