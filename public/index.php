<?php

declare(strict_types=1);

use Asad\OAuth2\Client\Provider\Zoho;
use DI\Container;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Settermjd\ZohoCRM\Application;
use Settermjd\ZohoCRM\Service\ZohoCrmService;
use Twilio\Rest\Client as TwilioRestClient;

require __DIR__ . '/../vendor/autoload.php';

const ZOHOCRM_URI = 'https://www.zohoapis.com.au/crm/v8/';
const ZOHO_SCOPE  = 'ZohoCRM.modules.contacts.READ,ZohoCRM.modules.events.READ';

// Load the required environment variables that the app needs
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required([
    'TWILIO_ACCOUNT_SID',
    'TWILIO_AUTH_TOKEN',
    'TWILIO_PHONE_NUMBER',
    'ZOHO_CLIENT_ID',
    'ZOHO_CLIENT_SECRET',
    'ZOHO_SCOPE',
    'ZOHO_SOID',
])->notEmpty();

// Set up the objects for the DI container's services
$provider = new Zoho([
    'clientId'     => $_ENV['ZOHO_CLIENT_ID'],
    'clientSecret' => $_ENV['ZOHO_CLIENT_SECRET'],
    'redirectUri'  => '',
    'dc'           => 'AU',
]);

$logger = (new Logger('name'))->pushHandler(
    new StreamHandler(
        __DIR__ . "/../app.log",
        Level::Debug
    )
);

try {
    $logger->debug("Attempting to retrieve access token.", [
        'scope' => $_ENV['ZOHO_SCOPE'],
        'soid'  => 'ZohoCRM.' . $_ENV['ZOHO_SOID'],
    ]);
    $accessToken = $provider->getAccessToken(
        'client_credentials',
        [
            'scope' => $_ENV['ZOHO_SCOPE'],
            'soid'  => 'ZohoCRM.' . $_ENV['ZOHO_SOID'],
        ]
    );
} catch (IdentityProviderException $e) {
    exit("Could not retrieve access token. Reason: " . $e->getMessage());
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

// Set up the DI container, initialising all of the required services
$container = new Container();
$container->set(LoggerInterface::class, function () use ($logger) {
    return $logger;
});
$container->set(Zoho::class, fn () => $provider);
$container->set(Client::class, fn () => $client);
$container->set(TwilioRestClient::class, fn () => $twilioRestClient);
$container->set(ZohoCrmService::class, fn () => new ZohoCrmService($client, $twilioRestClient, []));

$application = new Application(
    $container,
    [
        "TWILIO_PHONE_NUMBER" => $_ENV["TWILIO_PHONE_NUMBER"],
    ]
);

$application->setupRoutes();
$application->run();
