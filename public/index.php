<?php

declare(strict_types=1);

use Asad\OAuth2\Client\Provider\Zoho;
use DI\Container;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Settermjd\ZohoCRM\Application;
use Settermjd\ZohoCRM\Service\ZohoCrmService;
use Twilio\Rest\Client as TwilioRestClient;

require __DIR__ . '/../vendor/autoload.php';

const ZOHOCRM_URI = 'https://www.zohoapis.com.au/crm/v8/';
const ZOHO_SCOPE = 'ZohoCRM.modules.contacts.READ,ZohoCRM.modules.events.READ';

$repository = RepositoryBuilder::createWithDefaultAdapters()
    ->allowList(
        [
            'MEETING_CREATOR',
            'MEETING_VENUE',
            'TWILIO_ACCOUNT_SID',
            'TWILIO_AUTH_TOKEN',
            'TWILIO_PHONE_NUMBER',
            'ZOHO_CLIENT_ID',
            'ZOHO_CLIENT_SECRET',
            'ZOHO_SCOPE',
            'ZOHO_SOID',
        ]
    )
    ->make();

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required([
    'MEETING_CREATOR',
    'MEETING_VENUE',
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
    'ZOHO_CLIENT_ID',
    'ZOHO_CLIENT_SECRET',
    'ZOHO_SCOPE',
    'ZOHO_SOID',
])->notEmpty();

$provider = new Zoho([
    'clientId'     => $_ENV['ZOHO_CLIENT_ID'],
    'clientSecret' => $_ENV['ZOHO_CLIENT_SECRET'],
    'redirectUri'  => '',
    'dc'           => 'AU',
]);

try {
    $accessToken = $provider->getAccessToken(
        'client_credentials',
        [
            'scope' => $_ENV['ZOHO_SCOPE'],
            'soid'  => 'ZohoCRM.' . $_ENV['ZOHO_SOID'],
        ]
    );
} catch (IdentityProviderException $e) {
    exit($e->getMessage());
}

$client = new Client(
    [
        'base_uri' => ZOHOCRM_URI,
        'debug'    => false,
        'headers'  => [
            "Authorization" => sprintf("Zoho-oauthtoken %s", $accessToken),
        ],
        'timeout'  => 2.0,
    ]
);

$twilioRestClient = new TwilioRestClient(
    $_ENV["TWILIO_ACCOUNT_SID"],
    $_ENV["TWILIO_AUTH_TOKEN"]
);

$container = new Container();
$container->set(Zoho::class, fn() => $provider);
$container->set(Client::class, fn() => $client);
$container->set(TwilioRestClient::class, fn() => $twilioRestClient);
$container->set(ZohoCrmService::class, fn() => new ZohoCrmService($client, $twilioRestClient, []));

$application = new Application(
    $container,
    [
        "TWILIO_PHONE_NUMBER" => $_ENV["TWILIO_PHONE_NUMBER"],
    ]
);

$application->setupRoutes();
$application->run();
