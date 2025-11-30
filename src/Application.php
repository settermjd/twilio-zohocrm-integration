<?php

declare(strict_types=1);

namespace App;

use App\Entity\CallPurpose;
use App\Entity\CallResult;
use App\Entity\CallType;
use App\Entity\LoggedCall;
use App\Entity\OutgoingCallStatus;
use App\Entity\SearchResponse\Event;
use App\Service\ZohoCrmService;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client as TwilioRestClient;
use Twilio\TwiML\VoiceResponse;

use function explode;
use function gmdate;
use function json_encode;
use function sprintf;

/**
 * This class encapsulates the central Slim application, making it easier to create and test.
 */
final class Application
{
    /**
     * The date format to use for sending DateTime values to ZohoCrm
     */
    public const string DATE_FORMAT = 'PT%dM%dS';

    private App $app;

    /**
     * @param array{ 'TWILIO_PHONE_NUMBER':string, 'PUBLIC_URL':string } $options
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $options,
    ) {
        AppFactory::setContainer($container);
        $this->app = AppFactory::createFromContainer($container);
    }

    public function setupRoutes(): void
    {
        $this->app->post('/', [$this, 'handleDefaultRoute']);
        $this->app->post('/call/end', [$this, 'endCall']);
        $this->app->post('/call/receive', [$this, 'receiveCall']);
        $this->app->post('/call/record', [$this, 'recordCall']);
        $this->app->get('/fields/{module}', [$this, 'getFieldMetadata']);

        // Test for Zoho CRM call logging
        $this->app->post('/call/test', [$this, 'testCallLogging']);
    }

    /**
     * receiveCall receives a request from Twilio and returns TwiML instructing it to handle a voice call
     *
     * Specifically, it returns TwiML prompting the user for what to say and to transcribe the call when completed.
     *
     * @see https://www.twilio.com/docs/voice/twiml/record
     * @see https://www.twilio.com/docs/voice/twiml/record#transcribe
     * @see https://www.twilio.com/en-us/voice/pricing/us
     */
    public function receiveCall(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        // Generate the TwiML to handle the voice call
        $voiceResponse = new VoiceResponse();
        $voiceResponse->say("Please leave a message at the beep.\nPress the hash or pound key when finished.");
        $voiceResponse->record([
            'action'             => sprintf('%s/call/end', $this->options['PUBLIC_URL']),
            'finishOnKey'        => '#',
            'method'             => 'POST',
            'transcribe'         => true,
            'transcribeCallback' => sprintf('%s/call/record', $this->options['PUBLIC_URL']),
        ]);

        // Write the generated TwiML as the response's body
        $response->getBody()->write($voiceResponse->asXML());
        return $response->withHeader("Content-Type", 'application/xml');
    }

    public function endCall(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $voiceResponse = new VoiceResponse();
        $voiceResponse->say("Thank you for recording your message. It will be logged in Zoho CRM.");
        $voiceResponse->hangup();

        // Write the generated TwiML as the response's body
        $response->getBody()->write($voiceResponse->asXML());
        return $response;
    }

    /**
     * recordCall logs a voice call with Zoho CRM
     *
     * It receives call details from a POST webhook request from Twilio, then retrieves the call data, and uses the
     * combined information to log the voice call with Zoho CRM. It links the voice recording to the call record,
     * along with the text copy of the call in the description field.
     *
     * @see https://www.zoho.com/crm/developer/docs/api/v8/insert-records.html
     */
    public function recordCall(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        /** @var TwilioRestClient $twilioClient */
        $twilioClient = $this->app->getContainer()->get(TwilioRestClient::class);

        /** @var LoggerInterface $logger */
        $logger = $this->app->getContainer()->get(LoggerInterface::class);

        $formData = $request->getParsedBody();

        $logger->debug('Form Data', $formData);

        $call = $twilioClient->calls($formData['CallSid'])->fetch();

        $callData                     = new LoggedCall();
        $callData->callType           = CallType::INBOUND;
        $callData->outgoingCallStatus = OutgoingCallStatus::COMPLETED;
        $callData->callStarted        = $call->startTime;

        // Instantiate a DateInterval instance based on the call's integer duration
        [$minutes, $seconds]    = explode(':', gmdate('i:s', (int) $call->duration));
        $callData->callDuration = new DateInterval(sprintf(self::DATE_FORMAT, $minutes, $seconds));

        $callData->subject        = sprintf(
            "Inbound Call From Twilio (%s)",
            $callData->callDuration->format(DateTimeImmutable::ATOM)
        );
        $callData->voiceRecording = $formData['RecordingUrl'];

        // Purpose of the outgoing call
        $callData->callPurpose = CallPurpose::PROSPECTING;

        // Not going to fill this in, yet.
        $callData->callAgenda = "";

        // Outcome of the outgoing call
        $callData->callResult  = CallResult::REQUESTED_MORE_INFO;
        $callData->description = $formData['TranscriptionText'];

        /** @var ZohoCrmService $zohoCrmService */
        $zohoCrmService = $this->app
            ->getContainer()
            ->get(ZohoCrmService::class);

        $logger->debug('Call Data', [$callData]);
        $result = $zohoCrmService->recordVoiceCall($callData);

        $result
            ? $response->getBody()->write("Call logged.")
            : $response->getBody()->write("Call not logged.");

        return $result;
    }

    /**
     * @param array<string,mixed> $args
     */
    public function getFieldMetadata(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        /** @var ZohoCrmService $zohoCrmService */
        $zohoCrmService = $this->app
            ->getContainer()
            ->get(ZohoCrmService::class);

        $response
            ->getBody()
            ->write(
                $zohoCrmService->getFieldMetadata($args['module'])
            );

        return $response;
    }

    /**
     * This function provides the dispatcher/handler for the default route.
     */
    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $logger->debug('Webhook request data', (array) $request->getParsedBody());

        $meetingCreator = $request->getParsedBody()['Meeting_Creator'];
        $meetingVenue   = $request->getParsedBody()['Meeting_Location'];

        /** @var ZohoCrmService $zohoCrmService */
        $zohoCrmService = $this->app
            ->getContainer()
            ->get(ZohoCrmService::class);

        $meeting = $zohoCrmService->getEventDetails($meetingCreator, $meetingVenue);
        $result  = $this->notifyMeetingParticipants($meeting);

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * notifyMeetingParticipants notifies meeting participants about the upcoming meeting via SMS
     *
     * @return array<string,string>
     */
    public function notifyMeetingParticipants(Event $event): array
    {
        $smsStatus = [];

        foreach ($event->participants as $participant) {
            $msgBody = <<<EOF
            You've been requested to attend a meeting (%s) at %s, starting at %s. 

            The meeting organiser is %s. 
            Email them at %s for more information.
            EOF;

            /** @var TwilioRestClient $twilioRestClient */
            $twilioRestClient = $this->app->getContainer()->get(TwilioRestClient::class);

            try {
                $message = $twilioRestClient->messages->create(
                    $participant->contactDetails->phone,
                    [
                        "body" => sprintf(
                            $msgBody,
                            $event->title,
                            $event->venue,
                            (new DateTimeImmutable($event->startsAt))->format("r"),
                            $event->organiser->name,
                            $event->organiser->email
                        ),
                        "from" => $this->options["TWILIO_PHONE_NUMBER"],
                    ]
                );

                $smsStatus[$participant->participant] = $message->status;
            } catch (RestException $e) {
                $smsStatus[$participant->participant] = $e->getMessage();
            }
        }

        return $smsStatus;
    }

    public function run(): void
    {
        $this->app->run();
    }
}
