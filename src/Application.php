<?php

declare(strict_types=1);

namespace Settermjd\ZohoCRM;

use DateTimeImmutable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Settermjd\ZohoCRM\Entity\SearchResponse\Event;
use Settermjd\ZohoCRM\Service\ZohoCrmService;
use Slim\App;
use Slim\Factory\AppFactory;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client as TwilioRestClient;

use function json_encode;
use function sprintf;

/**
 * This class encapsulates the central Slim application, making it easier to create and test.
 */
final class Application
{
    private App $app;

    /**
     * @param array{'TWILIO_PHONE_NUMBER': string} $options
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
    }

    /**
     * This function provides the dispatcher/handler for the default route.
     */
    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        /** @var ZohoCrmService $zohoCrmService */
        $zohoCrmService = $this->app
            ->getContainer()
            ->get(ZohoCrmService::class);


        $meetingCreator = $request->getParsedBody()['Meeting_Creator'];
        $meetingVenue   = $request->getParsedBody()['Meeting_Location'];

        $meeting = $zohoCrmService->getEventDetails($meetingCreator, $meetingVenue);
        $result  = $this->notifyMeetingParticipants($meeting);

        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * notifyMeetingParticipants notifies meeting participants about the upcoming meeting via SMS
     *
     * @param list<EventParticipant> $participants
     */
    public function notifyMeetingParticipants(Event $event): array
    {
        $smsStatus = [];

        foreach ($event->participants as $participant) {
            $msgBody = <<<EOF
            You've been requested to attend a meeting (%s) at %s, starting at %s. 

            The event organiser is %s. 
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
                            (new DateTimeImmutable($event->startsAt))->format("D, M jS, Y"),
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
