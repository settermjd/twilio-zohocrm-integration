<?php

declare(strict_types=1);


namespace Settermjd\SendGrid;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory;

class Application
{
    private App $app;

    public function __construct(ContainerInterface $container)
    {
        // Set up the DI container
        AppFactory::setContainer($container);

        // Instantiate a new Slim App object
        $this->app = AppFactory::createFromContainer($container);
    }

    private function setupRoutes(): void
    {
        $this->app->post('/', [$this, 'handleDefaultRoute']);
    }

    public function handleDefaultRoute(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        
    }

    public function run(): void
    {
        $this->app->run();
    }
}
