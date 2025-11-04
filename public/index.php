<?php

declare(strict_types=1);

use Asad\OAuth2\Client\Provider\Zoho;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use GuzzleHttp\Client;
use JSON\Unmarshal;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Settermjd\ZohoCRM\Entity\SearchResponse\Contact;
use Settermjd\ZohoCRM\Entity\SearchResponse\Event;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

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

$app = AppFactory::create();

$app->get(
    '/',
    function (Request $request, Response $response, array $args) {
        $provider = new Zoho([
            'clientId'     => $_ENV['ZOHO_CLIENT_ID'],
            'clientSecret' => $_ENV['ZOHO_CLIENT_SECRET'],
            'redirectUri'  => '',
            'dc'           => 'AU',
        ]);

        $accessToken = "";
        try {
            // Try to get an access token using the client credentials grant.
            $accessToken = $provider->getAccessToken(
                'client_credentials',
                [
                    'scope' => $_ENV['ZOHO_SCOPE'],
                    'soid'  => 'ZohoCRM.' . $_ENV['ZOHO_SOID'],
                ]
            );
        } catch (IdentityProviderException $e) {
            // Failed to get the access token
            exit($e->getMessage());
        }

        // Search the API for meeting participants
        $client = new Client(
            [
                'base_uri' => 'https://www.zohoapis.com.au/crm/v8/',
                'timeout'  => 2.0,
            ]
        );
        $res    = $client->get(
            'Events/search',
            [
                'debug' => false,
                'headers' => [
                    "Authorization" => sprintf("Zoho-oauthtoken %s", $accessToken),
                ],
                'query' => 'criteria=' . sprintf(
                    '((Created_By:equals:%s)and(Venue:starts_with:%s))',
                    rawurlencode($_ENV['MEETING_CREATOR']),
                    rawurlencode($_ENV['MEETING_VENUE']),
                ),
            ]
        );

        // Marshal the participants data
        $body = $res->getBody();
        $jsonData = json_decode($body->getContents(), true);
        $event = new Event();
        Unmarshal::decode($event, $jsonData['data'][0]);

        // Get the phone number for each contact
        foreach ($event->participants as $participant) {
            $res    = $client->get(
                sprintf('Contacts/%s', $participant->participant),
                [
                    'debug' => false,
                    'headers' => [
                        "Authorization" => sprintf("Zoho-oauthtoken %s", $accessToken),
                    ],
                    'query' => 'fields=Phone,Mobile',
                ]
            );
            $jsonData = json_decode($res->getBody()->getContents(), true);
            $contact = new Contact();
            Unmarshal::decode($contact, $jsonData['data'][0]);

            // Notify the retrieved participants about the upcoming meeting
            // Take the mobile number and send a message
            $sid = getenv("TWILIO_ACCOUNT_SID");
            $token = getenv("TWILIO_AUTH_TOKEN");
            $twilio = new Client($sid, $token);
            $message = $twilio->messages->create(
                $contact->mobile,
                [
                    "body" => "This is the ship that made the Kessel Run in fourteen parsecs?",
                    "from" => getenv("TWILIO_PHONE_NUMBER"),
                ]
            );
        }

        return $response;
    }
);

$app->run();
