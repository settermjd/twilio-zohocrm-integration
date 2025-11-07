<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM\Service;

use GuzzleHttp\ClientInterface;
use JSON\Unmarshal;
use Settermjd\ZohoCRM\Entity\SearchResponse\Contact;
use Settermjd\ZohoCRM\Entity\SearchResponse\Event;
use Settermjd\ZohoCRM\Entity\SearchResponse\EventParticipant;
use Twilio\Rest\Client as TwilioRestClient;

use function json_decode;
use function rawurlencode;
use function sprintf;

final class ZohoCrmService
{
    public const ZOHOCRM_URI = 'https://www.zohoapis.com.au/crm/v8/';

    /**
     * @param array{
     *      'ZOHO_SCOPE': string, 
     *      'ZOHO_SOID': string, 
     *      'TWILIO_PHONE_NUMBER': string
     *  } $options
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly TwilioRestClient $twilioClient,
        private readonly array $options,
    ) {}

    /**
     * getEventDetails retrieves the event details from ZohoCRM
     */
    public function getEventDetails(string $creator, string $venue): Event
    {
        $response = $this->httpClient->request(
            'GET',
            'Events/search',
            [
                'query' => 'criteria=' . sprintf(
                    '((Created_By:equals:%s)and(Venue:starts_with:%s))',
                    rawurlencode($creator),
                    rawurlencode($venue),
                ),
            ]
        );

        $body     = $response->getBody();
        $jsonData = json_decode($body->getContents(), true);
        $event    = new Event();
        Unmarshal::decode($event, $jsonData['data'][0]);

        foreach ($event->participants as &$participant) {
            $participant->contactDetails = $this->getContactFromEventParticipant($participant);
        }

        return $event;
    }

    /**
     * getContactFromEventParticipant retrieves contact details based on an
     * event participant's id
     *
     * It retrieves just the phone and mobile number for an event participant,
     * as ZohoCRM's API doesn't return phone number information in the response
     * for retrieving event participant information
     */
    private function getContactFromEventParticipant(EventParticipant $participant): Contact
    {
        $response = $this->httpClient->request(
            'GET',
            sprintf('Contacts/%s', $participant->participant),
            [
                'query' => 'fields=Phone,Mobile',
            ]
        );
        $jsonData = json_decode($response->getBody()->getContents(), true);
        $contact  = new Contact();
        Unmarshal::decode($contact, $jsonData['data'][0]);

        return $contact;
    }
}
