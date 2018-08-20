<?php

declare(strict_types=1);

namespace Building\App;

use Building\Domain\Command;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rhumsaa\Uuid\Uuid;
use Zend\Diactoros\Response;

\call_user_func(function () {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    $sm = $container = require __DIR__ . '/../container.php';

    /** @var \Zend\Expressive\Application $app */
    $app = $sm->get(\Zend\Expressive\Application::class);
    $factory = $sm->get(\Zend\Expressive\MiddlewareFactory::class);

    (require __DIR__ . '/../config/pipeline.php')($app, $factory, $container);


    $app->get('/', function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        ob_start();
        require __DIR__ . '/../template/index.php';
        $content = ob_get_clean();

        $response = new Response();
        $response->getBody()->write($content);

        return $response;

    }, 'home');

    $app->get('/test-exception', function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
        throw new \Exception('whoops test');
    });

    $app->post('/register-new-building', function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($sm) : ResponseInterface {

        $commandBus = $sm->get('async-command-bus');
        $commandBus->dispatch(Command\RegisterNewBuilding::fromName($request->getParsedBody()['name']));

        return new Response\RedirectResponse('/');

        //$response = new Response();
        //return $response->withAddedHeader('Location', '/');
    });

    $app->get('/building/{buildingId}', function (ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface {
        $buildingId = Uuid::fromString($request->getAttribute('buildingId'));

        ob_start();
        require __DIR__ . '/../template/building.php';
        $content = ob_get_clean();

        $response = new Response();
        $response->getBody()->write($content);

        return $response;
    });

    $app->post('/checkin/{buildingId}', function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($sm) : ResponseInterface {
        // fire command here

        $buildingId = Uuid::fromString($request->getAttribute('buildingId'));

        $commandBus = $sm->get('async-command-bus');
        $commandBus->dispatch(Command\CheckInUser::fromBuildingAndUsername(
            $buildingId,
            $request->getParsedBody()['username']
        ));

        return new Response\RedirectResponse( '/building/' . $buildingId->toString());

        //return $response->withAddedHeader('Location', '/building/' . $buildingId->toString());
    });

    $app->post('/checkout/{buildingId}', function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($sm) : ResponseInterface {
        $buildingId = Uuid::fromString($request->getAttribute('buildingId'));

        $sm->get('async-command-bus')->dispatch(Command\CheckOutUser::fromBuildingAndUsername(
            $buildingId,
            $request->getParsedBody()['username']
        ));

        return new Response\RedirectResponse( '/building/' . $buildingId->toString());


    });

    $app->run();
});
