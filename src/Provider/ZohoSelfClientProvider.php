<?php

declare(strict_types=1);

namespace App\Provider;

use Asad\OAuth2\Client\Provider\Zoho;

class ZohoSelfClientProvider extends Zoho
{
    /** @var string define the ZohoCRM Self Client authorisation code */
    protected string $code = '';
}
