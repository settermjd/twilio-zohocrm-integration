<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Entity\SearchResponse;

use JSON\Attributes\JSON;

/**
 * This class stores a small number of details of a contact retrieved from Zoho CRM.
 *
 * @see https://www.zoho.com/developer/help/api/modules-fields.html#Contacts
 */
class Contact
{
    #[JSON(field: 'id')]
    public string $id;

    #[JSON(field: 'Phone')]
    public string $phone;

    #[JSON(field: 'Mobile')]
    public string $mobile;
}
