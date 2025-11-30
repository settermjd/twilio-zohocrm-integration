<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\LoggedCall;
use App\Entity\SearchResponse\Contact;
use App\Entity\SearchResponse\Event;
use App\Entity\SearchResponse\EventParticipant;
use DateTimeInterface;
use GuzzleHttp\ClientInterface;
use JSON\Unmarshal;
use Twilio\Rest\Client as TwilioRestClient;

use function json_decode;
use function rawurlencode;
use function sprintf;

/**
 * This class provides simplifies interacting with the Zoho CRM API
 *
 * In saying that, it doesn't do all that much, just simplifies what the
 * app needs to retrieve from the Zoho CRM API, to avoid storing the
 * information in public/index.php.
 */
final class ZohoCrmService
{
    /**
     * @param array{'TWILIO_PHONE_NUMBER': string} $options
     */
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly TwilioRestClient $twilioClient,
        private readonly array $options,
    ) {
    }


    /**
     * recordVoiceCall records a voice call in Zoho CRM
     *
     * It stores the text transcription of the call along with the recording.
     */
    public function recordVoiceCall(LoggedCall $callDetails): bool
    {
        $requestData = [
            'data' => [
                [
                    'Call_Agenda'              => $callDetails->callAgenda,
                    'Call_Duration'            => $callDetails->callDuration->format("i"),
                    'Call_Duration_in_seconds' => $callDetails->callDuration->format("s"),
                    'Call_Purpose'             => $callDetails->callPurpose->value,
                    'Call_Result'              => $callDetails->callResult->value,
                    'Call_Start_Time'          => $callDetails->callStarted->format(DateTimeInterface::ATOM),
                    'Call_Type'                => $callDetails->callType->value,
                    'Description'              => $callDetails->description,
                    'Outgoing_Call_Status'     => $callDetails->outgoingCallStatus->value,
                    'Subject'                  => $callDetails->subject,
                    'Voice_Recording__s'       => $callDetails->voiceRecording,
                ],
            ],
        ];

        $body     = $this->httpClient->request(
            'POST',
            'Calls',
            [
                'json' => $requestData,
            ]
        )->getBody();
        $jsonData = json_decode($body->getContents(), true);

        return true;
    }

    /**
     * getEventDetails retrieves the event details from Zoho CRM and marshalls them as an Event object
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
     * as Zoho CRM's API doesn't return phone number information in the response
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
