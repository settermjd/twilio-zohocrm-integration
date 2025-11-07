<?php

declare(strict_types=1);


namespace Settermjd\ZohoCRM;

use DateTimeImmutable;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Settermjd\ZohoCRM\Entity\SearchResponse\Event;
use Settermjd\ZohoCRM\Entity\SearchResponse\EventParticipant;
use Settermjd\ZohoCRM\Service\ZohoCrmService;
use Slim\App;
use Slim\Factory\AppFactory;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client as TwilioRestClient;

final class Application
{
    private App $app;

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly array $options,
    ) {
        AppFactory::setContainer($container);
        $this->app = AppFactory::createFromContainer($container);
    }

    public function setupRoutes(): void
    {
        $this->app->get('/', [$this, 'handleDefaultRoute']);
    }

    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        /** @var ZohoCrmService $zohoCrmService */
        $zohoCrmService = $this->app
            ->getContainer()
            ->get(ZohoCrmService::class);

        /** These two values need to be retrieved from the request */
        $eventCreator = $_ENV['MEETING_CREATOR'];
        $eventVenue = $_ENV['MEETING_VENUE'];

        $event = $zohoCrmService->getEventDetails($eventCreator, $eventVenue);
        $result = $this->notifyEventParticipants($event);
        $response->getBody()->write(json_encode($result));

        return $response;
    }

    /**
     * notifyEventParticipants notifies all of the event participants via SMS
     * 
     * @param list<EventParticipant> $participants
     */
    public function notifyEventParticipants(Event $event): array
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
